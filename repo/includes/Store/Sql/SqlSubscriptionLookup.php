<?php

namespace Wikibase\Store\Sql;

use Database;
use LoadBalancer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\SubscriptionLookup;

/**
 * Implementation of SubscriptionLookup based on a database table.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var LoadBalancer
	 */
	private $dbLoadBalancer;

	/**
	 * @param LoadBalancer $dbLoadBalancer
	 */
	public function __construct( LoadBalancer $dbLoadBalancer ) {
		$this->dbLoadBalancer = $dbLoadBalancer;
	}

	/**
	 * Returns a list of entities a given site is subscribed to.
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[] $entityIds The entities we are interested in, or null for "any".
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$dbr = $this->dbLoadBalancer->getConnection( DB_SLAVE );

		// NOTE: non-Item ids are ignored, since only items can be subscribed to
		//       via sitelinks.
		$entityIds = $this->reIndexEntityIds( $entityIds );
		$subscribedIds = $this->querySubscriptions( $dbr, $siteId, array_keys( $entityIds ) );

		// collect the item IDs present in these links
		$linkedEntityIds = array();
		foreach ( $subscribedIds as $id ) {
			$linkedEntityIds[$id] = $entityIds[$id];
		}

		$this->dbLoadBalancer->reuseConnection( $dbr );

		return $linkedEntityIds;
	}

	/**
	 * Return the existing subscriptions for given Id to check
	 *
	 * @param EntityId $idToCheck EntityId to get subscribers
	 *
	 * @return string[] wiki IDs of wikis subscribed to the given entity
	 */
	public function getSubscribers( EntityId $idToCheck ) {

		$where = [ 'cs_entity_id' => $idToCheck->getSerialization() ];
		$dbr = $this->dbLoadBalancer->getConnection( DB_REPLICA );

		$subscriptions = $dbr->selectFieldValues(
			'wb_changes_subscription',
			'cs_subscriber_id',
			$where,
			__METHOD__
		);

		$this->dbLoadBalancer->reuseConnection( $dbr );

		return $subscriptions;
	}

	/**
	 * For a set of potential subscriptions, returns the existing subscriptions.
	 *
	 * @param Database $db
	 * @param string $subscriber
	 * @param string[]|null $idsToCheck Id strings to check
	 *
	 * @return string[] Entity ID strings from $subscriptions which $subscriber is subscribed to.
	 */
	private function querySubscriptions( Database $db, $subscriber, array $idsToCheck = null ) {
		$where = array(
			'cs_subscriber_id' => $subscriber,
		);

		if ( $idsToCheck ) {
			$where['cs_entity_id'] = $idsToCheck;
		}

		return $db->selectFieldValues(
			'wb_changes_subscription',
			'cs_entity_id',
			$where,
			__METHOD__
		);
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] The ItemIds from EntityId[], keyed by numeric id.
	 */
	private function reIndexEntityIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

}
