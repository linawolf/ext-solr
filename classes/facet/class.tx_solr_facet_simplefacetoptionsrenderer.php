<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Markus Goldbach <markus.goldbach@dkd.de>
*  (c) 2012 Ingo Renner <ingo@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Default facet renderer.
 *
 * @author	Markus Goldbach <markus.goldbach@dkd.de>
 * @author Ingo Renner <ingo@typo3.org>
 */
class tx_solr_facet_SimpleFacetOptionsRenderer implements tx_solr_FacetOptionsRenderer {

	/**
	 * The facet's name as configured in TypoScript.
	 *
	 * @var	string
	 */
	protected $facetName;

	/**
	 * The facet's TypoScript configuration.
	 *
	 * @var	string
	 */
	protected $facetConfiguration;

	/**
	 * The facet options the user can select from.
	 *
	 * @var	array
	 */
	protected $facetOptions = array();

	/**
	 * Template engine to replace template markers with their values.
	 *
	 * @var	tx_solr_Template
	 */
	protected $template;

	/**
	 * The query which is going to be sent to Solr when a user selects a facet.
	 *
	 * @var	tx_solr_Query
	 */
	protected $query;

	/**
	 * Constructor
	 *
	 * @param string $facetName The facet's name
	 * @param array $facetOptions The facet's options.
	 * @param tx_solr_Template $template Template to use to render the facet
	 * @param tx_solr_Query $query Query instance used to build links.
	 */
	public function __construct($facetName, array $facetOptions, tx_solr_Template $template, tx_solr_Query $query) {
		$this->facetName          = $facetName;
		$this->facetOptions       = $facetOptions;

		$solrConfiguration        = tx_solr_Util::getSolrConfiguration();
		$this->facetConfiguration = $solrConfiguration['search.']['faceting.']['facets.'][$facetName . '.'];

		$this->query = $query;

		$this->template = clone $template;
	}

	/**
	 * Sets the link target page Id for links generated by the query linking
	 * methods.
	 *
	 * @param	integer	$pageId The link target page Id.
	 */
	public function setLinkTargetPageId($pageId) {
		$this->query->setLinkTargetPageId(intval($pageId));
	}

	/**
	 * Renders the complete facet.
	 *
	 * @see	tx_solr_FacetRenderer::render()
	 * @return	string	Rendered HTML representing the facet.
	 */
	public function renderFacetOptions() {
		$facetOptionLinks  = array();
		$solrConfiguration = tx_solr_Util::getSolrConfiguration();
		$this->template->workOnSubpart('single_facet_option');

		$i = 0;
		foreach ($this->facetOptions as $facetOption => $facetOptionResultCount) {
			$facetOption = (string) $facetOption;
			if ($facetOption == '_empty_') {
					// TODO - for now we don't handle facet missing.
				continue;
			}

			$facetOption = t3lib_div::makeInstance('tx_solr_facet_FacetOption',
				$this->query,
				$this->facetName,
				$facetOption,
				$facetOptionResultCount
			);	/* @var $facetOption tx_solr_facet_FacetOption */

			$optionText    = $facetOption->render($this->facetConfiguration);
			$optionLink    = $facetOption->getAddFacetOptionLink($optionText);
			$optionLinkUrl = $facetOption->getAddFacetOptionUrl();

			$optionHidden = '';
			if (++$i > $solrConfiguration['search.']['faceting.']['limit']) {
				$optionHidden = 'tx-solr-facet-hidden';
			}

			$optionSelected = $facetOption->isSelectedInFacet($this->facetName);

				// negating the facet option links to remove a filter
			if ($this->facetConfiguration['selectingSelectedFacetOptionRemovesFilter']
			&& $optionSelected) {
				$optionLink    = $facetOption->getRemoveFacetOptionLink($optionText);
				$optionLinkUrl = $facetOption->getRemoveFacetOptionUrl();
			}

			if ($this->facetConfiguration['singleOptionMode']) {
				$optionLink    = $facetOption->getReplaceFacetOptionLink($optionText);
				$optionLinkUrl = $facetOption->getReplaceFacetOptionUrl();
			}

			$facetOptionLinks[] = array(
				'hidden'     => $optionHidden,
				'link'       => $optionLink,
				'url'        => $optionLinkUrl,
				'text'       => $optionText,
				'value'      => $facetOption->getValue(),
				'count'      => $facetOption->getNumberOfResults(),
				'selected'   => $optionSelected ? '1' : '0',
				'facet_name' => $this->facetName
			);
		}

		$this->template->addLoop('facet_links', 'facet_link', $facetOptionLinks);

		return $this->template->render();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/facet/class.tx_solr_facet_simplefacetoptionsrenderer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/facet/class.tx_solr_facet_simplefacetoptionsrenderer.php']);
}

?>