<?php

namespace MediaWiki\Skins\Shared;

use MediaWiki\MediaWikiServices;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;

class ConfigHelper {

	/**
	 * Determine whether the configuration should be disabled on the page.
	 *
	 * @param array $options read from MediaWiki configuration.
	 *   $params = [
	 *      'exclude' => [
	 *            'mainpage' => (bool) should it be disabled on the main page?
	 *            'namespaces' => int[] namespaces it should be excluded on.
	 *            'querystring' => string regex for patterns the query strings it should be excluded on
	 *                     e.g. 'action=.*' all actions
	 *            'pagetitles' => string[] of pages it should be excluded on.
	 *                     For special pages, use canonical English name.
	 *      ]
	 *   ]
	 * @param WebRequest $request
	 * @param Title|null $title
	 *
	 * @return bool
	 */
	public static function shouldDisable( array $options, WebRequest $request, Title $title = null ) {
		$canonicalTitle = $title != null ? $title->getRootTitle() : null;

		$exclusions = $options[ 'exclude' ] ?? [];

		if ( $title != null && $title->isMainPage() ) {
			// only one check to make
			return $exclusions[ 'mainpage' ] ?? false;
		} elseif ( $title != null && $canonicalTitle != null && $canonicalTitle->isSpecialPage() ) {
			$spFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
			[ $canonicalName, $par ] = $spFactory->resolveAlias( $canonicalTitle->getDBKey() );
			if ( $canonicalName ) {
				$canonicalTitle = Title::makeTitle( NS_SPECIAL, $canonicalName );
			}
		}

		//
		// Check the excluded page titles based on the canonical title
		//
		// Now we have the canonical title and the exclusions link we look for any matches.
		$pageTitles = $exclusions[ 'pagetitles' ] ?? [];
		foreach ( $pageTitles as $titleText ) {
			// use strtolower to make sure the config passed for special pages
			// is case insensitive, so it does not generate a wrong special page title
			$titleText = $canonicalTitle->isSpecialPage() ? strtolower( $titleText ) : $titleText;
			$excludedTitle = Title::newFromText( $titleText );

			if ( $canonicalTitle != null && $canonicalTitle->equals( $excludedTitle ) ) {
				return true;
			}
		}

		//
		// Check the exclusions
		// If nothing matches the exclusions to determine what should happen
		//
		$excludeNamespaces = $exclusions[ 'namespaces' ] ?? [];
		// Night Mode is disabled on certain namespaces
		if ( $title != null && $title->inNamespaces( $excludeNamespaces ) ) {
			return true;
		}
		$excludeQueryString = $exclusions[ 'querystring' ] ?? [];

		foreach ( $excludeQueryString as $param => $excludedParamPattern ) {
			$paramValue = $request->getRawVal( $param );
			if ( $paramValue !== null ) {
				if ( $excludedParamPattern === '*' ) {
					// Backwards compatibility for the '*' wildcard.
					$excludedParamPattern = '.+';
				}
				return (bool)preg_match( "/$excludedParamPattern/", $paramValue );
			}
		}

		return false;
	}
}
