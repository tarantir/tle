<?php
/**
 * 	TLE/Parser
 * 	@version 1.0
 *	@author Ivan Stanojevic ivanstan@gmail.com
 *
 * 	@link https://github.com/ivanstan/tle Available on GitHub.
 */
namespace TLE;

/**
 * Class Parser.
 * Parses the Two Element Set obtained from NASA/NORAD
 *
 * @package TLE
 */
class Parser
{

	/**
	 * Universal Gravitational Constant
	 */
	const GRAVITY_C = 0;

	/**
	 * Mass of Earth
	 */
	const EARTH_MASS = 0;

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
	 * Calculated Semi-Major Axis based on Mean Motion.
	 *
	 * @var	float
	 */
	public $semiMajorAxis;

	/**
	 * Orbit number the satellite has finished from deployment to orbit until the specified TLE epoch.
	 *
	 * @var float
	 */
	public $revolutionNumber;

	/**
	 * Class Constructor.
	 *
	 * @param $tleString	Two line element set
	 *
	 * @throws \Exception
	 */
	public function __construct($tleString) {
		$this->dateTimeZone = new \DateTimeZone('UTC');

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

		$line1 = reset($lines);
		$line2 = end($lines);

		$checksum      = $this->calculateChecksum($line1);
		$checkSumLine1 = trim(substr($line1, 68));

		echo "<pre>";
		print_r($checksum);
		echo "</pre>";

		echo "<pre>";
		print_r($checkSumLine1);
		echo "</pre>";

		$checksum      = $this->calculateChecksum($line2);
		$checkSumLine2 = trim(substr($line2, 68));

		echo "<pre>";
		print_r($checksum);
		echo "</pre>";

		echo "<pre>";
		print_r($checkSumLine2);
		echo "</pre>";

		$this->satelliteNumber                   = substr($line1, 2, 6);
		$this->classification                    = substr($line1, 7, 1);
		$this->internationalDesignatorLaunchYear = trim(substr($line1, 9, 2));

		if($this->internationalDesignatorLaunchYear) {
			$dateTime = \DateTime::createFromFormat('y', $this->internationalDesignatorLaunchYear);
			$dateTime->setTimezone($this->dateTimeZone);
			$this->internationalDesignatorLaunchYear = $dateTime->format('Y');
		}

		$this->internationalDesignatorLaunchNumber = trim(substr($line1, 12, 2));
		$this->internationalDesignatorLaunchPiece  = trim(substr($line1, 14, 2));

		$this->epochYear = trim(substr($line1, 18, 2));
		$dateTime        = \DateTime::createFromFormat('y', $this->epochYear);
		$dateTime->setTimezone($this->dateTimeZone);
		$this->epochYear     = $dateTime->format('Y');
		$this->epoch         = trim(substr($line1, 18, 14));
		$epoch               = explode('.', trim(substr($line1, 20, 12)));
		$this->epochDay      = $epoch[0];
		$this->epochFraction = '0.' . $epoch[1];
		$seconds             = round(86400 * $this->epochFraction);
		$date                = new \DateTime();
		$date->setTimezone($this->dateTimeZone);
		$date->setDate($this->epochYear, 1, 1);
		$date->setTime(0, 0, 0);
		$this->epochUnixTimestamp = $date->format('U') + (86400 * $this->epochDay) + $seconds - 86400;

		$this->meanMotionFirstDerivative   = '0' . trim(substr($line1, 33, 10));
		$this->meanMotionSecondDerivative  = trim(substr($line1, 44, 8));
		$this->bStarDragTerm               = trim(substr($line1, 53, 8));
		$this->elementNumber               = trim(substr($line1, 64, 4));
		$this->satelliteNumber             = trim(substr($line2, 2, 6));
		$this->inclination                 = trim(substr($line2, 8, 8));
		$this->rightAscensionAscendingNode = trim(substr($line2, 17, 8));
		$this->eccentricity                = '0.' . trim(substr($line2, 26, 7));
		$this->argumentPerigee             = trim(substr($line2, 34, 8));
		$this->meanAnomaly                 = trim(substr($line2, 43, 8));
		$this->meanMotion                  = trim(substr($line2, 52, 11));
		$this->semiMajorAxis = pow((self::GRAVITY_C * self::EARTH_MASS) / (2 * pi() * $this->meanMotion)^2, 1/3);
		$this->revolutionNumber = trim(substr($line2, 63, 5));
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
	 * @param $line
	The Line 1 Checksum is determined by adding all the previous numbers in Line 1 and taking the last digit in the final sum. All letters, periods and plus signs are taken as "0". Negative signs are taken as "1".
	 *
	 * This is mainly used to verify the first line's authenticity and/or its integrity upon receipt.
	 *
	 * For the ISS TLE - Line 1:
	 *
	 * 1+2+5+5+4+4+U(0)+9+8+0+6+7+A(0)+0+6+0+5+2+.(0)+3+4+7+6+7+3+6+1+.(0)+0+0+0+1+3+9+4+9+0+0+0+0+0+-(1)+0+9+7+1+2+7+-(1)+4+0+3+9+3
	 * = 174
	 *
	 * Taking the last digit gives a Line 1 Checksum of 4, which matches the final digit in Line 1.
	 *
	 * Many orbit propagators do not read or use this value.
	 */

	public function calculateChecksum($line) {
		$sum = 0;
		for($i = 0; $i < strlen($line); $i++) {
			if($line[$i] == '-') {
				$sum += 1;
			} elseif(is_numeric($line[$i])) {
				$sum += $line[$i];
			}

		}

		return $sum % 10;
	}
}
