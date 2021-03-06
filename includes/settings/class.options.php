<?php

/**
 * Class to manage options using the Options API.
 *
 * @todo This really needs some cleaning up.
 *
 * @package     Connections
 * @subpackage  Manage the plugins options.
 * @copyright   Copyright (c) 2013, Steven A. Zahm
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       unknown
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get and Set the plugin options
 */
class cnOptions {
	/**
	 * Array of options returned from WP get_option method.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * String: plugin version.
	 *
	 * @var float
	 */
	private $version;

	/**
	 * String: plugin db version.
	 *
	 * @var float
	 */
	private $dbVersion;

	private $defaultTemplatesSet;
	private $activeTemplates;

	/**
	 * Current time as reported by PHP in Unix timestamp format.
	 *
	 * @var integer
	 */
	public $currentTime;

	/**
	 * Current time as reported by WordPress in Unix timestamp format.
	 *
	 * @var integer
	 */
	public $wpCurrentTime;

	/**
	 * Current time as reported by MySQL in Unix timestamp format.
	 *
	 * @var integer
	 */
	public $sqlCurrentTime;

	/**
	 * The time offset difference between the PHP time and the MySQL time in Unix timestamp format.
	 *
	 * @var integer
	 */
	public $sqlTimeOffset;

	/**
	 * Sets up the plugin option properties. Requires the current WP user ID.
	 *
	 * @param interger $userID
	 */
	public function __construct() {
		global $wpdb;

		$this->options = get_option( 'connections_options' );

		$this->version = ( isset( $this->options['version'] ) && ! empty( $this->options['version'] ) ) ? $this->options['version'] : CN_CURRENT_VERSION;
		$this->dbVersion = ( isset( $this->options['db_version'] ) && ! empty( $this->options['db_version'] ) ) ? $this->options['db_version'] : CN_DB_VERSION;

		$this->defaultTemplatesSet = $this->options['settings']['template']['defaults_set'];
		$this->activeTemplates = (array) $this->options['settings']['template']['active'];

		$this->defaultRolesSet = isset( $this->options['settings']['roles']['defaults_set'] ) && ! empty( $this->options['settings']['roles']['defaults_set'] ) ? $this->options['settings']['roles']['defaults_set'] : FALSE;

		$this->wpCurrentTime = current_time( 'timestamp' );
		$this->currentTime = date( 'U' );

		/*
		 * Because MySQL FROM_UNIXTIME returns timestamps adjusted to the local
		 * timezone it is handy to have the offset so it can be compensated for.
		 * One example is when using FROM_UNIXTIME, the timestamp returned will
		 * not be the actual stored timestamp, it will be the timestamp adjusted
		 * to the timezone set in MySQL.
		 */
		$mySQLTimeStamp = $wpdb->get_results( 'SELECT NOW() as timestamp' );
		$this->sqlCurrentTime = strtotime( $mySQLTimeStamp[0]->timestamp );
		$this->sqlTimeOffset = time() - $this->sqlCurrentTime;
	}

	/**
	 * Saves the plug-in options to the database.
	 */
	public function saveOptions() {
		$this->options['version'] = $this->version;
		$this->options['db_version'] = $this->dbVersion;

		$this->options['settings']['template']['defaults_set'] = $this->defaultTemplatesSet;
		$this->options['settings']['template']['active'] = $this->activeTemplates;

		$this->options['settings']['roles']['defaults_set'] = $this->defaultRolesSet;

		update_option( 'connections_options', $this->options );
	}

	public function removeOptions() {
		delete_option( 'connections_options' );
	}

	/**
	 *
	 *
	 * @TODO This can likely be removed.
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 *
	 *
	 * @TODO This can likely be removed.
	 */
	public function setOptions( $options ) {
		$this->options = $options;
	}

	/**
	 * Require the user to be logged in to view the directory.
	 *
	 * @since 0.7.3
	 * @return bool
	 */
	public function getAllowPublic() {
		global $connections;

		$required = $connections->settings->get( 'connections', 'connections_login', 'required' ) ? FALSE : TRUE;

		return $required;
	}

	/**
	 * Disable the shortcode option - public_override.
	 *
	 * @deprecated since 0.7.3
	 * @return int
	 */
	public function getAllowPublicOverride() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_visibility', 'allow_public_override' ) ? TRUE : FALSE;
	}

	/**
	 * Disable the shortcode option - private_override.
	 *
	 * @deprecated since 0.7.3
	 * @return int
	 */
	public function getAllowPrivateOverride() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_visibility', 'allow_private_override' ) ? TRUE : FALSE;
	}

	public function getVisibilityOptions() {

		$options = array(
			'public'   => __( 'Public', 'connections' ),
			'private'  => __( 'Private', 'connections' ),
			'unlisted' => __( 'Unlisted', 'connections' )
			);

		foreach ( $options as $key => $option ) {

			if ( ! cnValidate::userPermitted( $key ) ) {

				unset( $options[ $key ] );
			}
		}

		return $options;
	}

	/**
	 * Returns $version.
	 *
	 * @see options::$version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Sets $version.
	 *
	 * @param object  $version
	 * @see options::$version
	 */
	public function setVersion( $version ) {
		$this->version = $version;
		$this->saveOptions();
	}

	/**
	 * Returns $dbVersion.
	 *
	 * @see options::$dbVersion
	 */
	public function getDBVersion() {
		return $this->dbVersion;
	}

	/**
	 * Sets $dbVersion.
	 *
	 * @param string  $dbVersion
	 * @see options::$dbVersion
	 */
	public function setDBVersion( $version ) {
		$this->dbVersion = $version;
		$this->saveOptions();
	}

	/**
	 * Returns $defaultTemplatesSet.
	 *
	 * @see cnOptions::$defaultTemplatesSet
	 */
	public function getDefaultTemplatesSet() {
		return $this->defaultTemplatesSet;
	}

	/**
	 * Sets $defaultTemplatesSet.
	 *
	 * @param object  $defaultTemplatesSet
	 * @see cnOptions::$defaultTemplatesSet
	 */
	public function setDefaultTemplatesSet( $defaultTemplatesSet ) {
		$this->defaultTemplatesSet = $defaultTemplatesSet;
	}

	public function getCapabilitiesSet() {
		return $this->defaultRolesSet;
	}

	public function defaultCapabilitiesSet( $defaultRolesSet ) {
		$this->defaultRolesSet = $defaultRolesSet;
	}

	/**
	 * Returns all active templates by type.
	 *
	 * @return (array)
	 */
	public function getAllActiveTemplates( ) {
		return $this->activeTemplates;
	}

	/**
	 * Returns the active templates by type.
	 *
	 * @param string  $type
	 * @return (array)
	 */
	public function getActiveTemplate( $type ) {
		return empty( $this->activeTemplates[ $type ]['slug'] ) ? '' : $this->activeTemplates[ $type ]['slug'];
	}

	/**
	 * Sets $activeTemplate by type.
	 *
	 * @param string  $type
	 * @param object  $activeTemplate
	 */
	public function setActiveTemplate( $type, $slug ) {
		$this->activeTemplates[ $type ] = array( 'slug' => $slug );
	}

	public function setDefaultTemplates() {

		$this->setActiveTemplate( 'all', 'card' );
		$this->setActiveTemplate( 'individual', 'card' );
		$this->setActiveTemplate( 'organization', 'card' );
		$this->setActiveTemplate( 'family', 'card' );
		$this->setActiveTemplate( 'anniversary', 'anniversary-light' );
		$this->setActiveTemplate( 'birthday', 'birthday-light' );

		$this->defaultTemplatesSet = TRUE;
	}

	/**
	 * Returns an array of the default family relation types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultFamilyRelationValues() {

		$options = array(
			'aunt'             => __( 'Aunt', 'connections' ),
			'brother'          => __( 'Brother', 'connections' ),
			'brotherinlaw'     => __( 'Brother-in-law', 'connections' ),
			'cousin'           => __( 'Cousin', 'connections' ),
			'daughter'         => __( 'Daughter', 'connections' ),
			'daughterinlaw'    => __( 'Daughter-in-law', 'connections' ),
			'father'           => __( 'Father', 'connections' ),
			'fatherinlaw'      => __( 'Father-in-law', 'connections' ),
			'friend'           => __( 'Friend', 'connections' ),
			'granddaughter'    => __( 'Grand Daughter', 'connections' ),
			'grandfather'      => __( 'Grand Father', 'connections' ),
			'grandmother'      => __( 'Grand Mother', 'connections' ),
			'grandson'         => __( 'Grand Son', 'connections' ),
			'greatgrandmother' => __( 'Great Grand Mother', 'connections' ),
			'greatgrandfather' => __( 'Great Grand Father', 'connections' ),
			'husband'          => __( 'Husband', 'connections' ),
			'mother'           => __( 'Mother', 'connections' ),
			'motherinlaw'      => __( 'Mother-in-law', 'connections' ),
			'nephew'           => __( 'Nephew', 'connections' ),
			'niece'            => __( 'Niece', 'connections' ),
			'partner'          => __( 'Partner', 'connections' ),
			'significant_other'=> __( 'Significant Other', 'connections' ),
			'sister'           => __( 'Sister', 'connections' ),
			'sisterinlaw'      => __( 'Sister-in-law', 'connections' ),
			'spouse'           => __( 'Spouse', 'connections' ),
			'son'              => __( 'Son', 'connections' ),
			'soninlaw'         => __( 'Son-in-law', 'connections' ),
			'stepbrother'      => __( 'Step Brother', 'connections' ),
			'stepdaughter'     => __( 'Step Daughter', 'connections' ),
			'stepfather'       => __( 'Step Father', 'connections' ),
			'stepmother'       => __( 'Step Mother', 'connections' ),
			'stepsister'       => __( 'Step Sister', 'connections' ),
			'stepson'          => __( 'Step Son', 'connections' ),
			'uncle'            => __( 'Uncle', 'connections' ),
			'wife'             => __( 'Wife', 'connections' )
		);

		return apply_filters( 'cn_family_relation_options', $options );
	}

	/**
	 * Returns the fmaily relation name based on the supplied key.
	 *
	 * @access private
	 * @since unknown
	 * @param unknown $value string
	 * @return string
	 */
	public function getFamilyRelation( $value ) {
		$relations = $this->getDefaultFamilyRelationValues();

		return $relations[$value];
	}

	/**
	 * Returns an array of the default address types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultAddressValues() {

		$options = array(
			'home'   => __( 'Home' , 'connections' ),
			'work'   => __( 'Work' , 'connections' ),
			'school' => __( 'School' , 'connections' ),
			'other'  => __( 'Other' , 'connections' )
		);

		return apply_filters( 'cn_address_options', $options );
	}

	/**
	 * Returns an array of the default phone types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultPhoneNumberValues() {

		$options = array(
			'homephone' => __( 'Home Phone' , 'connections' ),
			'homefax'   => __( 'Home Fax' , 'connections' ),
			'cellphone' => __( 'Cell Phone' , 'connections' ),
			'workphone' => __( 'Work Phone' , 'connections' ),
			'workfax'   => __( 'Work Fax' , 'connections' )
		);

		return apply_filters( 'cn_phone_options', $options );
	}

	/**
	 * Returns an array of the default social media types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultSocialMediaValues() {

		$options = array(
			'angieslist'    => 'Angie\'s List',
			'delicious'     => 'delicious',
			'cdbaby'        => 'CD Baby',
			'facebook'      => 'Facebook',
			'flickr'        => 'Flickr',
			'foursquare'    => 'foursquare',
			'goodreads'     => 'Goodreads',
			'googleplus'    => 'Google+',
			'houzz'         => 'Houzz',
			'imdb'          => 'IMDb',
			'instagram'     => 'Instagram',
			'itunes'        => 'iTunes',
			'linked-in'     => 'Linked-in',
			'mixcloud'      => 'mixcloud',
			'myspace'       => 'MySpace',
			'odnoklassniki' => 'Odnoklassniki',
			'pinterest'     => 'Pinterest',
			'podcast'       => 'Podcast',
			'reverbnation'  => 'ReverbNation',
			'rss'           => 'RSS',
			'smugmug'       => 'Smugmug',
			'soundcloud'    => 'SoundCloud',
			'technorati'    => 'Technorati',
			'tripadvisor'   => 'TripAdvisor',
			'tumblr'        => 'Tumblr',
			'twitter'       => 'Twitter',
			'vimeo'         => 'vimeo',
			'vk'            => 'VK',
			'yelp'          => 'Yelp',
			'youtube'       => 'YouTube'
		);

		return apply_filters( 'cn_social_network_options', $options );
	}

	/**
	 * Returns an array of the default IM types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultIMValues() {

		$options = array(
			'aim'       => 'AIM',
			'yahoo'     => 'Yahoo IM',
			'jabber'    => 'Jabber / Google Talk',
			'messenger' => 'Messenger',
			'skype'     => 'Skype',
			'icq'       => 'ICQ'
		);

		return apply_filters( 'cn_instant_messenger_options', $options );
	}

	/**
	 * Returns an array of the default email types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultEmailValues() {

		$options = array(
			'personal' => __( 'Personal Email' , 'connections' ),
			'work'     => __( 'Work Email' , 'connections' )
		);

		return apply_filters( 'cn_email_options', $options );
	}

	/**
	 * Returns an array of the default link types.
	 *
	 * @access private
	 * @since unknown
	 * @return array
	 */
	public function getDefaultLinkValues() {

		$options = array(
			'website' => __( 'Website' , 'connections' ),
			'blog'    => __( 'Blog' , 'connections' )
		);

		return apply_filters( 'cn_link_options', $options );
	}

	/**
	 * Returns an array of the default date types.
	 *
	 * @access private
	 * @since 0.7.3
	 * @return array
	 */
	public function getDateOptions() {

		$options = array(
			'anniversary'          => __( 'Anniversary' , 'connections' ),
			'baptism'              => __( 'Baptism' , 'connections' ),
			'birthday'             => __( 'Birthday' , 'connections' ),
			'deceased'             => __( 'Deceased' , 'connections' ),
			'certification'        => __( 'Certification' , 'connections' ),
			'employment'           => __( 'Employment' , 'connections' ),
			'membership'           => __( 'Membership' , 'connections' ),
			'graduate_high_school' => __( 'Graduate High School' , 'connections' ),
			'graduate_college'     => __( 'Graduate College' , 'connections' ),
			'ordination'           => __( 'Ordination' , 'connections' )
		);

		return apply_filters( 'cn_date_options', $options );
	}

	/**
	 * Return "1" if debug messages are enabled, otherwise, returns empty.
	 *
	 * @deprecated since 0.7.3
	 * @return mixed
	 */
	public function getDebug() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_debug', 'debug_messages' );
	}

	/**
	 * Return "1" if the Google Maps API is to be loaded, otherwise, returns empty.
	 *
	 * @deprecated since 0.7.3
	 * @return mixed
	 */
	public function getGoogleMapsAPI() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_compatibility', 'google_maps_api' );
	}

	/**
	 * Return "1" if the javascript are to be loaded in the page footer, otherwise, returns empty.
	 *
	 * @deprecated since 0.7.3
	 * @return mixed
	 */
	public function getJavaScriptFooter() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_compatibility', 'javascript_footer' );
	}

	/**
	 * Get the user's search field choices.
	 *
	 * @deprecated since 0.7.3
	 * @return array
	 */
	public function getSearchFields() {
		global $connections;

		return $connections->settings->get( 'connections', 'connections_search', 'fields' );
	}

	/**
	 * Return the registered content blocks.
	 *
	 * @access public
	 * @since  0.8
	 * @static
	 * @param  string $item [optional] The content block key id to return the title.
	 * @return mixed        [array | string] An associated array where the key if the content block ID and the value is the content bloack name. Or just the content block name if the id is supplied.
	 */
	public static function getContentBlocks( $item = NULL, $type = NULL ) {

		$blocks['items']    = apply_filters( 'cn_content_blocks', array( 'meta' => __( 'Custom Fields', 'connections' ) ) );
		$blocks['required'] = apply_filters( 'cn_content_blocks_required', array() );

		if ( ! is_null( $type ) && is_string( $type ) ) {

			$blockType['items']    = apply_filters( "cn_content_blocks-{$type}", array() );
			$blockType['required'] = apply_filters( "cn_content_blocks_required-{$type}", array() );

			$blocks['items']    = array_merge( $blocks['items'], $blockType['items'] );
			$blocks['required'] = array_merge( $blocks['required'], $blockType['required'] );
		}

		if ( is_null( $item ) && is_string( $type ) ) return $blocks;

		foreach ( $blocks['items'] as $block => $name ) {

			if ( $item == $block ) return $name;
		}

		return FALSE;
	}

	/**
	 * Get Base Country
	 *
	 * @since
	 * @return string $country The two letter country code for the base country.
	 */
	public static function getBaseCountry() {

		// Have to use get_option rather than cnSettingsAPI::get() because the class is not yet intialized.
		$baseGEO = get_option( 'connections_geo', array( 'base_country' => 'US', 'base_region' => 'WA' ) );

		return apply_filters( 'cn_base_country', $baseGEO['base_country'] );
	}

	/**
	 * Get Base State
	 *
	 * @since
	 * @return string $state The base region code.
	 */
	public static function getBaseRegion() {

		// Have to use get_option rather than cnSettingsAPI::get() because the class is not yet intialized.
		$baseGEO = get_option( 'connections_geo', array( 'base_country' => 'US', 'base_region' => 'WA' ) );

		return apply_filters( 'cn_base_region', $baseGEO['base_region'] );
	}
}
