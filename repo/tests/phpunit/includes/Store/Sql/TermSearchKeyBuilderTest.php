<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;
use Wikibase\TermSearchKeyBuilder;
use Wikibase\TermSqlIndex;

/**
 * @covers Wikibase\TermSearchKeyBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseTerm
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermSearchKeyBuilderTest extends \MediaWikiTestCase {

	public function termProvider() {
		$argLists = array();

		$argLists[] = array( 'en', 'FoO', 'fOo', true );
		$argLists[] = array( 'ru', 'Берлин', '  берлин  ', true );

		$argLists[] = array( 'en', 'FoO', 'bar', false );
		$argLists[] = array( 'ru', 'Берлин', 'бе55585рлин', false );

		return $argLists;
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testRebuildSearchKey( $languageCode, $termText, $searchText, $matches ) {
		/* @var TermSqlIndex $termCache */
		$termCache = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();

		// make term in item
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( $languageCode, $termText );

		// save term
		$termCache->clear();
		$termCache->saveTermsOfEntity( $item );

		// remove search key
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( $termCache->getTableName(), array( 'term_search_key' => '' ), array(), __METHOD__ );

		// rebuild search key
		$builder = new TermSearchKeyBuilder( $termCache );
		$builder->setRebuildAll( true );
		$builder->rebuildSearchKey();

		// remove search key
		$criteria = new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchText ] );

		$options = array(
			'caseSensitive' => false,
		);

		$obtainedTerms = $termCache->getMatchingTerms( array( $criteria ), TermIndexEntry::TYPE_LABEL, Item::ENTITY_TYPE, $options );

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

}
