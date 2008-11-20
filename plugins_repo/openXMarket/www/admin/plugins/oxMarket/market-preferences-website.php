<?php
/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| Copyright (c) 2000-2003 the phpAdsNew developers                          |
| For contact details, see: http://www.phpadsnew.com/                       |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/
require_once 'market-common.php';
phpAds_registerGlobalUnslashed(
    'affiliateid',
    'types',
    'attributes',
    'categories',
    'update_website_mkt_preferences'
);

$oComponent = OX_Component::factory('admin', 'oxMarket');

if (isset($update_website_mkt_preferences)) {
    processMarketplacePreferences($affiliateid, $types, $attributes, $categories, $oComponent);
}
else {
    displayPage($affiliateid, $oComponent);
}


function processMarketplacePreferences($affiliateId, $aType, $aAttribute, $aCategory, &$oComponent)
{
    if ($oComponent->updateWebsiteRestrictions($affiliateId, $aType, $aAttribute, $aCategory)
        && $oComponent->storeWebsiteRestrictions($affiliateId, $aType, $aAttribute, $aCategory)) {
            OA_Admin_UI::queueMessage('Website settings has been updated', 'local', 'confirm', 3000);
    }
    else {
            OA_Admin_UI::queueMessage('Unable to update website settings', 'local', 'error', 0);
    }

    //TODO redirect to the same page for now just redisplay
    displayPage($affiliateId, $oComponent);
}

function displayPage($affiliateid, &$oComponent)
{
    phpAds_PageHeader("market-preferences-website",'','../../');
    $oTpl    = new OA_Plugin_Template('market-preferences-website.html','openXMarket');

    $aSelected = $oComponent->getWebsiteRestrictions($affiliateid);

    $aCreativeAttributes = getCreativeAttributes($aSelected[SETTING_TYPE_CREATIVE_ATTRIB], $oComponent);
    $aCreativeTypes = getCreativeTypes($aSelected[SETTING_TYPE_CREATIVE_TYPE], $oComponent);
    $aAdCategories = getAdCategories($aSelected[SETTING_TYPE_CREATIVE_CATEGORY], $oComponent);


    //split attributes into groups
    $attrCount = count($aCreativeAttributes);
    $size = getOptimalItemSize($attrCount);
    if ($attrCount == $size) {
        $aAttrCols[] = $aCreativeAttributes;
    }
    else {
        $aAttrCols = array_chunk($aCreativeAttributes, $size);
    }

    //split attributes into groups
    $catCount = count($aAdCategories);
    $size = getOptimalItemSize($catCount);
    if ($catCount == $size) {
        $aAdCatCols[] = $aAdCategories;
    }
    else {
        $aAdCatCols = array_chunk($aAdCategories, $size);
    }

    $oTpl->assign('aCreativeTypes', $aCreativeTypes);
    $oTpl->assign('aCreativeAttributes', $aAttrCols);
    $oTpl->assign('aAdCategories', $aAdCatCols);
    $oTpl->assign('affiliateId', $affiliateid);


    $oTpl->display();

    phpAds_PageFooter();
}

function getOptimalItemSize($count)
{
    $optimalRowCount = 8;

    if ($count <= $optimalRowCount) { //one column
        $size = $count;
    }
    else if ($count > 40) { //no more than 4 cols
        $size = ceil($count / 4);
    }
    else {
        $size = $optimalRowCount;
    }

    return $size;
}


function getAdCategories($aSelected, &$oComponent)
{
    $aAdCategories = $oComponent->oMarketPublisherClient->getAdCategories();
    reformatIdNameArray($aAdCategories);
    markCheckedIds($aAdCategories, $aSelected);
    return $aAdCategories;
}


function getCreativeTypes($aSelected, &$oComponent)
{
    $aCreativeTypes = $oComponent->oMarketPublisherClient->getCreativeTypes();
    reformatIdNameArray($aCreativeTypes);
    markCheckedIds($aCreativeTypes, $aSelected);
    return $aCreativeTypes;
}


function getCreativeAttributes($aSelected, &$oComponent)
{
    $aCreativeAttributes = $oComponent->oMarketPublisherClient->getCreativeAttributes();
    reformatIdNameArray($aCreativeAttributes);
    markCheckedIds($aCreativeAttributes, $aSelected);
    return $aCreativeAttributes;
}

/**
 * Array in format id => name is changed to format id => array ( 'id' => id, 'name' => name )
 * to allow freely add other properties to given id
 *
 * @param array $aElements in/out array of elements
 */
function reformatIdNameArray(&$aElements) {
    foreach ($aElements as $id => $name) {
        $aElements[$id] = array( 'id' => $id, 'name' => $name);
    }
}

/**
 * To array of elements in format id => array ( _attributes_ )
 * attribute 'checked' is added for ids included in $aSelected array
 *
 * @param array $aElements in/out array of elements
 * @param array $aSelected array of ids to mark as checked
 */
function markCheckedIds(&$aElements, $aSelected) {
    if (!empty($aSelected)) {
        foreach ($aSelected as $id) {
            if (isset($aElements[$id]))  { //check if there is such element (attribute/type/category)
                $aElements[$id]['checked'] = true;
            }
        }
    }
}
?>