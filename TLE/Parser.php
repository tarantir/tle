<?php
/**
 *    TLE/Parser
 *
 * @version 1.0
 * @author  Ivan Stanojevic ivanstan@gmail.com
 *
 * @link    https://github.com/ivanstan/tle Available on GitHub.
 */
namespace TLE;

use \TLE\Constant;
use \TLE\LatLng;

/**
 * Class Parser.
 * Parses the Two Element Set obtained from NASA/NORAD
 *
 * @package TLE
 */
class Parser
{
	private $dateTimeZone;

	/**
	 * Orbit direction.
	 */
	const ORBIT_PROGRADE = 1;
	const ORBIT_RETROGRADE = 2;

	/**
	 * International satellite classification constants.
	 */
	const SATELLITE_UNCLASSIFIED = 'U';
	const SATELLITE_CLASSIFIED = 'C';
	const SATELLITE_SECRET = 'S';

	/**
	 * First Line String.
	 *
	 * @var string
	 */
	public $firstLine;

	/**
	 * Second Line String.
	 *
	 * @var string
	 */
	public $secondLine;

	/**
	 * Parsed checksum of the first line.
	 *
	 * @var int
	 */
	public $firstLineChecksum;

	/**
	 * Calculated checksum value of the first line.
	 *
	 * @var int
	 */
	public $firstLineCalculatedChecksum;

	/**
	 * Parsed checksum of the second line.
	 *
	 * @var int
	 */
	public $secondLineChecksum;

	/**
	 * Calculated checksum value of the second line.
	 *
	 * @var int
	 */
	public $secondLineCalculatedChecksum;

	/**
	 * Twenty-four character name (to be consistent with the name length in the NORAD Satellite Catalog).
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Satellite/Catalog Number.
	 *
	 * @var int
	 */
	public $satelliteNumber;

	/**
	 * Satellite Classification. Either SATELLITE_UNCLASSIFIED, SATELLITE_CLASSIFIED or SATELLITE_SECRET.
	 *
	 * @var string
	 */
	public $classification;

	/**
	 * Satellite Launch Year.
	 *
	 * @var int
	 */
	public $internationalDesignatorLaunchYear;

	/**
	 * Number of launch in Launch Year
	 *
	 * @var int
	 */
	public $internationalDesignatorLaunchNumber;

	/**
	 * Satellite piece. "A" for primary payload. Subsequent lettering indicates secondary payloads and rockets that were
	 * directly involved in the launch process or debris detected originating from the primary launch payload.
	 *
	 * @var string
	 */
	public $internationalDesignatorLaunchPiece;

	/**
	 * A TLE Epoch indicates the UTC time when the TLE's indicated orbit elements were true.
	 *
	 * @var string
	 */
	public $epoch;

	/**
	 * Year of the epoch when TLE is taken.
	 *
	 * @var int
	 */
	public $epochYear;

	/**
	 * Day of the year when TLE was taken.
	 *
	 * @var string
	 */
	public $epochDay;

	/**
	 * Decimal (fraction) days.
	 *
	 * @var float
	 */
	public $epochFraction;

	/**
	 * Calculated Linux Timestamp when TLE is taken.
	 *
	 * @var int
	 */
	public $epochUnixTimestamp;

	/**
	 * Elapsed time in seconds from TLE Epoch.
	 *
	 * @var mixed
	 */
	public $deltaSec;

	/**
	 * Half of the Mean Motion First Time Derivative, measured in orbits per day per day (orbits/day^2).
	 *
	 * @var float
	 */
	public $meanMotionFirstDerivative;

	/**
	 * One sixth the Second Time Derivative of the Mean Motion, measured in orbits per day per day per day
	 * (orbits/day3).
	 *
	 * @var string
	 */
	public $meanMotionSecondDerivative;

	/**
	 * B-Star Drag Term estimates the effects of atmospheric drag on the satellite's motion.
	 *
	 * @var string
	 */
	public $bStarDragTerm;

	/**
	 * Element Set Number is used to distinguish a specific satellite TLE from its predecessors and successors.
	 * Integer value incremented for each calculated TLE starting with day of launch.
	 *
	 * @var int
	 */
	public $elementNumber;

	/**
	 * Orbital inclination.
	 *
	 * @var string
	 */
	public $inclination;

	/**
	 * Right Ascension of Ascending Node expressed in degrees (aW0).
	 *
	 * @var float
	 */
	public $rightAscensionAscendingNode;

	/**
	 * @var float    Orbit eccentricity (e).
	 */
	public $eccentricity;

	/**
	 * Argument of Perigee expressed in degrees (w0).
	 *
	 * @var string
	 */
	public $argumentPerigee;

	/**
	 * Mean Anomaly M(t).
	 *
	 * @var string
	 */
	public $meanAnomaly;

	/**
	 * Mean Motion (n) defined as number of revolutions satellite finished around Earth in one solar day(24 hours).
	 *
	 * @var float
	 */
	public $meanMotion;

	/**
	 * Mean Motion expressed in radians per second.
	 *
	 * @var float
	 */
	public $meanMotionRadSec;

	/**
	 * Time of one revolution expressed in seconds.
	 *
	 * @var float
	 */
	public $revTime;

	/**
	 * Calculated Semi-Major Axis based on Mean Motion.
	 *
	 * @var    float
	 */
	public $semiMajorAxis;

	/**
	 * Calculated Semi-Minor Axis.
	 *
	 * @var    float
	 */
	public $semiMinorAxis;

	/**
	 * Orbit number the satellite has finished from deployment to orbit until the specified TLE epoch.
	 *
	 * @var float
	 */
	public $revolutionNumber;

	private $eccentricAnomaly;
	private $cosEccentricAnomaly;
	private $sinEccentricAnomaly;

	/**
	 * Class Constructor.
	 *
	 * @param $tleString    string    TLE Data.
	 *
	 * @throws \Exception
	 */
	public function __construct($tleString) {
		$this->dateTimeZone    = new \DateTimeZone('UTC');
		$this->currentDateTime = getdate();

		$lines = explode("\n", $tleString);

		switch(count($lines)) {
			case 2:

				break;
			case 3:
				$this->name = substr($lines[0], 0, 24);
				unset($lines[0]);
				break;
			default:
				throw new \Exception('Invalid two element set');
		}

		$this->firstLine  = trim(reset($lines));
		$this->secondLine = trim(end($lines));

		$this->firstLineChecksum           = (int)trim(substr($this->firstLine, 68));
		$this->firstLineCalculatedChecksum = (int)$this->calculateChecksum($this->firstLine);

		if($this->firstLineChecksum != $this->firstLineCalculatedChecksum) {
			throw new \Exception('TLE First Line Checksum fail');
		}

		$this->secondLineChecksum           = (int)trim(substr($this->secondLine, 68));
		$this->secondLineCalculatedChecksum = (int)$this->calculateChecksum($this->secondLine);

		if($this->secondLineChecksum != $this->secondLineCalculatedChecksum) {
			throw new \Exception('TLE Second Line Checksum fail');
		}

		$this->satelliteNumber                   = (int)substr($this->firstLine, 2, 6);
		$this->classification                    = substr($this->firstLine, 7, 1);
		$this->internationalDesignatorLaunchYear = (int)trim(substr($this->firstLine, 9, 2));

		if($this->internationalDesignatorLaunchYear) {
			$dateTime = \DateTime::createFromFormat('y', $this->internationalDesignatorLaunchYear);
			$dateTime->setTimezone($this->dateTimeZone);
			$this->internationalDesignatorLaunchYear = (int)$dateTime->format('Y');
		}

		$this->internationalDesignatorLaunchNumber = (int)trim(substr($this->firstLine, 12, 2));
		$this->internationalDesignatorLaunchPiece  = trim(substr($this->firstLine, 14, 2));
		$this->epochYear                           = trim(substr($this->firstLine, 18, 2));
		$dateTime                                  = \DateTime::createFromFormat('y', $this->epochYear);
		$dateTime->setTimezone($this->dateTimeZone);
		$this->epochYear                   = (int)$dateTime->format('Y');
		$this->epoch                       = trim(substr($this->firstLine, 18, 14));
		$epoch                             = explode('.', trim(substr($this->firstLine, 20, 12)));
		$this->epochDay                    = (int)$epoch[0];
		$this->epochFraction               = '0.' . $epoch[1];
		$this->epochUnixTimestamp          = $this->getEpochUnixTimestamp();
		$this->deltaSec                    = $this->getDeltaSec();
		$this->meanMotionFirstDerivative   = trim(substr($this->firstLine, 33, 10));
		$this->meanMotionSecondDerivative  = trim(substr($this->firstLine, 44, 8));
		$this->bStarDragTerm               = trim(substr($this->firstLine, 53, 8));
		$this->elementNumber               = (int)trim(substr($this->firstLine, 64, 4));
		$this->satelliteNumber             = (int)trim(substr($this->secondLine, 2, 6));
		$this->inclination                 = (float)trim(substr($this->secondLine, 8, 8));
		$this->rightAscensionAscendingNode = (float)trim(substr($this->secondLine, 17, 8));
		$this->eccentricity                = (float)('.' . trim(substr($this->secondLine, 26, 7)));
		$this->argumentPerigee             = (float)trim(substr($this->secondLine, 34, 8));
		$this->meanAnomaly                 = (float)trim(substr($this->secondLine, 43, 8));
		$this->meanMotion                  = (float)trim(substr($this->secondLine, 52, 11));
		$this->meanMotionRadSec            = ($this->meanMotion * 2 * M_PI) / 86400;
		$this->revTime                     = $this->meanMotion * \Constant::SIDERAL_DAY_SEC;
		$this->semiMajorAxis               = pow((\Constant::eg_4pi * pow($this->revTime, 2)), (1 / 3));
		$this->semiMinorAxis               = $this->semiMajorAxis * sqrt(1 - pow($this->eccentricity, 2));
		$this->revolutionNumber            = (int)trim(substr($this->secondLine, 63, 5));
		$this->satelliteRange              = $this->getSatelliteRange();
		$satellitePoint                    = $this->getSatellitePoint();
		$this->satelliteLatLng             = new \LatLng($satellitePoint['latitude'], $satellitePoint['longitude']);
	}

	/**
	 * Check if orbit is retrograde. Inclination larger 90 degrees.
	 *
	 * @return bool
	 */
	public function orbitRetrograde() {
		return ($this->inclination > 90) ? true : false;
	}

	/**
	 * Check if orbit is prograde. Inclination less than 90 degrees.
	 *
	 * @return bool
	 */
	public function orbitPrograde() {
		return ($this->inclination < 90) ? true : false;
	}

	/**
	 * Check if orbit is polar. Inclination equal to 90 degrees.
	 *
	 * @return bool
	 */
	public function orbitPolar() {
		return ($this->inclination == 90) ? true : false;
	}

	/**
	 * Calculates checksum (Modulo 10) for TLE line.
	 *
	 * @param $line TLE line
	 *
	 * @return int
	 */
	public function calculateChecksum($line) {
		$line = substr($line, 0, strlen($line) - 1);
		$sum  = 0;
		for($i = 0; $i < strlen($line); $i++) {
			if($line[$i] == '-') {
				$sum += 1;
			} elseif(is_numeric($line[$i])) {
				$sum += $line[$i];
			}

		}

		return $sum % 10;
	}

	private function getSatelliteRange() {
		$radsSinceEpoch     = ($this->meanMotionRadSec * $this->deltaSec) + deg2rad($this->meanAnomaly); // (Mean Motion * Elapsed time) + Mean Anomaly
		$fractionRevolution = $radsSinceEpoch - (2 * M_PI * (floor($radsSinceEpoch / (2 * M_PI))));

		$this->eccentricAnomaly = $fractionRevolution;
		do {
			$this->cosEccentricAnomaly = cos($this->eccentricAnomaly);
			$this->sinEccentricAnomaly = sin($this->eccentricAnomaly);
			$denom                     = 1 - ($this->cosEccentricAnomaly * $this->eccentricity);
			$iter                      = ($this->eccentricAnomaly - ($this->eccentricity * $this->sinEccentricAnomaly) - $radsSinceEpoch) / $denom;
			$this->eccentricAnomaly    = $this->eccentricAnomaly - $iter;
		} while(abs($iter) > 0.0001);

		return $this->semiMajorAxis * $denom;
	}

	/**
	 * Calculate Unix timestamp from TLE Epoch.
	 *
	 * @return string
	 */
	private function getEpochUnixTimestamp() {
		$seconds = round(86400 * $this->epochFraction);
		$date    = new \DateTime();
		$date->setTimezone($this->dateTimeZone);
		$date->setDate($this->epochYear, 1, 1);
		$date->setTime(0, 0, 0);

		return $date->format('U') + (86400 * $this->epochDay) + $seconds - 86400;
	}

	/**
	 * Calculate Delta Sec.
	 *
	 * @return mixed
	 */
	private function getDeltaSec() {
		return $this->currentDateTime[0] - $this->epochUnixTimestamp;
	}

	private function getSatellitePoint() {
		// Calculating Satellite position vector on the Orbital Plane
		$satOrbitalPlaneX = $this->semiMajorAxis * ($this->cosEccentricAnomaly - $this->eccentricity);
		$satOrbitalPlaneY = $this->semiMinorAxis * $this->sinEccentricAnomaly;

		// Partial Rotation Matrix to transform from the Orbital Plane to Inertial (Celestial) Coordinates
		$cosArgumentPerigee = cos(deg2rad($this->argumentPerigee));
		$sinArgumentPerigee = sin(deg2rad($this->argumentPerigee));
		$cosRaan     = cos(deg2rad($this->rightAscensionAscendingNode));
		$sinRaan     = sin(deg2rad($this->rightAscensionAscendingNode));
		$cosInclination    = cos(deg2rad($this->inclination));
		$sinInclination    = sin(deg2rad($this->inclination));

		$cel_x_x = ($cosArgumentPerigee * $cosRaan) - ($sinArgumentPerigee * $sinRaan * $cosInclination);
		$cel_x_y = (-$sinArgumentPerigee * $cosRaan) - ($cosArgumentPerigee * $sinRaan * $cosInclination);
		$cel_y_x = ($cosArgumentPerigee * $sinRaan) + ($sinArgumentPerigee * $cosRaan * $cosInclination);
		$cel_y_y = (-$sinArgumentPerigee * $sinRaan) + ($cosArgumentPerigee * $cosRaan * $cosInclination);
		$cel_z_x = ($sinArgumentPerigee * $sinInclination);
		$cel_z_y = ($cosArgumentPerigee * $sinInclination);

		// Calculations Satellite position vector in Celestial Coordinates
		$satCelestialX = ($satOrbitalPlaneX * $cel_x_x) + ($satOrbitalPlaneY * $cel_x_y);
		$satCelestialY = ($satOrbitalPlaneX * $cel_y_x) + ($satOrbitalPlaneY * $cel_y_y);
		$satCelestialZ = ($satOrbitalPlaneX * $cel_z_x) + ($satOrbitalPlaneY * $cel_z_y);

		$extraEarthRotationPerDay = (2 * M_PI) / \Constant::Tropical_year;
		// The total earth rotation in one Solar Day = 1 sideral day + the above figure
		$earthRotRadSec = ($extraEarthRotationPerDay + (2 * M_PI)) / 86400;

		$deltaGhaaSec   = $this->epochUnixTimestamp[0] - (strtotime(\Constant::date_of_GHAA));
		$currentGhaaRad = deg2rad(\Constant::ghaa_deg) + ($deltaGhaaSec * $earthRotRadSec);
		$cosGhaa        = cos(-$currentGhaaRad);
		$sinGhaa        = sin(-$currentGhaaRad);

		// Satellite Coordinates in Geocentric Equatorial Coordinates (from RA to LONG, etc.)
		$satGeoCX = ($satCelestialX * $cosGhaa) - ($satCelestialX * $sinGhaa);
		$satGeoCY = ($satCelestialX * $sinGhaa) + ($satCelestialX * $cosGhaa);
		$satGeoCZ = $satCelestialZ;

		$satelliteLongitude = rad2deg(atan2($satGeoCY, $satGeoCX));
		$satelliteLatitude  = rad2deg(asin($satGeoCZ / $this->satelliteRange));

		return array(
			'latitude' => $satelliteLongitude, 'longitude' => $satelliteLatitude,
		);
	}
}
