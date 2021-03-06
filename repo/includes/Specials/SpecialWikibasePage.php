<?php

namespace Wikibase\Repo\Specials;

use SpecialPage;
use UserBlockedError;
use Wikibase\StringNormalizer;

/**
 * Base for special pages of the Wikibase extension,
 * holding some scaffolding and preventing us from needing to
 * deal with weird SpecialPage insanity (ie $this->mFile inclusion)
 * in every base class.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibasePage extends SpecialPage {

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @since 0.4
	 *
	 * @param string $name
	 * @param string $restriction
	 * @param bool   $listed
	 */
	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( $name, $restriction, $listed );

		// XXX: Use StringNormalizer as a plain composite - since it
		//      doesn't have any dependencies, local instantiation isn't an issue.
		$this->stringNormalizer = new StringNormalizer();
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'wikibase';
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * @see SpecialPage::setHeaders
	 *
	 * @since 0.1
	 */
	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( $this->getDescription() );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader( 'wikibase-' . strtolower( $this->getName() ) . '-summary' );

		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
		}
	}

	/**
	 * Checks if user is blocked, and if he is blocked throws a UserBlocked.
	 *
	 * @throws UserBlockedError
	 */
	protected function checkBlocked() {
		if ( $this->getUser()->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}
	}

	/**
	 * @param string $error The error message in HTML format
	 */
	protected function showErrorHTML( $error ) {
		$this->getOutput()->addHTML( '<p class="error">' . $error . '</p>' );
	}

}
