<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 3/14/2015
 * Time: 9:50 PM
 */
class LatLng {

	public $latitude;
	public $longitude;

	function __construct($latitude, $longitude) {
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}
}