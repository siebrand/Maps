<?php

/**
 * Class for the 'finddestination' parser hooks, which can find a
 * destination given a starting point, an initial bearing and a distance.
 * 
 * @since 0.7
 * 
 * @file Maps_Finddestination.php
 * @ingroup Maps
 * 
 * @author Jeroen De Dauw
 */
class MapsFinddestination extends ParserHook {
	
	/**
	 * No LST in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */
	public static function staticMagic( array &$magicWords, $langCode ) {
		$className = __CLASS__;
		$instance = new $className();
		return $instance->magic( $magicWords, $langCode );
	}
	
	/**
	 * No LST in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */	
	public static function staticInit( Parser &$wgParser ) {
		$className = __CLASS__;
		$instance = new $className();
		return $instance->init( $wgParser );
	}	
	
	/**
	 * Gets the name of the parser hook.
	 * @see ParserHook::getName
	 * 
	 * @since 0.7
	 * 
	 * @return string
	 */
	protected function getName() {
		return 'finddestination';
	}
	
	/**
	 * Returns an array containing the parameter info.
	 * @see ParserHook::getParameterInfo
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getParameterInfo() {
		global $egMapsAvailableServices, $egMapsAvailableGeoServices, $egMapsDefaultGeoService, $egMapsAvailableCoordNotations;
		global $egMapsCoordinateNotation, $egMapsAllowCoordsGeocoding, $egMapsCoordinateDirectional;	 
		
		return array(
			'location' => array(
				'required' => true,
				'tolower' => false
			),
			'bearing' => array(
				'type' => 'float',
				'required' => true
			),
			'distance' => array(
				'type' => 'float',
				'required' => true
			),
			'mappingservice' => array(
				'criteria' => array(
					'in_array' => $egMapsAvailableServices
				),
				'default' => false
			),
			'service' => array(
				'criteria' => array(
					'in_array' => $egMapsAvailableGeoServices
				),
				'default' => $egMapsDefaultGeoService
			),
			'format' => array(
				'criteria' => array(
					'in_array' => $egMapsAvailableCoordNotations
				),
				'aliases' => array(
					'notation'
				),
				'default' => $egMapsCoordinateNotation
			),
			'allowcoordinates' => array(
				'type' => 'boolean',
				'default' => $egMapsAllowCoordsGeocoding
			),
			'directional' => array(
				'type' => 'boolean',
				'default' => $egMapsCoordinateDirectional
			),
		);
	}
	
	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getDefaultParameters() {
		return array( 'location', 'bearing', 'distance' );
	}
	
	/**
	 * Renders and returns the output.
	 * @see ParserHook::render
	 * 
	 * @since 0.7
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public function render( array $parameters ) {
		$canGeocode = MapsMapper::geocoderIsAvailable();
			
		if ( $canGeocode ) {
			$location = MapsGeocoders::attemptToGeocode( $parameters['location'] );
		} else {
			$location = MapsCoordinateParser::parseCoordinates( $parameters['location'] );
		}
		
		if ( $location ) {
			$destination = MapsGeoFunctions::findDestination(
				$location,
				$parameters['bearing'],
				MapsDistanceParser::parseDistance( $parameters['distance'] )
			);
			$output = MapsCoordinateParser::formatCoordinates( $destination, $parameters['format'], $parameters['directional'] );
		} else {
			global $egValidatorFatalLevel;
			switch ( $egValidatorFatalLevel ) {
				case Validator_ERRORS_NONE:
					$output = '';
					break;
				case Validator_ERRORS_WARN:
					$output = '<b>' . htmlspecialchars( wfMsgExt( 'validator_warning_parameters', array( 'parsemag' ), 1 ) ) . '</b>';
					break;
				case Validator_ERRORS_SHOW: default:
					// Show an error that the location could not be geocoded or the coordinates where not recognized.
					if ( $canGeocode ) {
						$output = htmlspecialchars( wfMsgExt( 'maps_geocoding_failed', array( 'parsemag' ), $parameters['location'] ) );
					} else {
						$output = htmlspecialchars( wfMsgExt( 'maps-invalid-coordinates', array( 'parsemag' ), $parameters['location'] ) );
					}
					break;
			}
		}
			
		return $output;
	}
	
}