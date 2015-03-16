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
	 * Time of one revolution expressed in seconds.
	 *
	 * @var float
	 */
	public $revTime;

	/**
	 * Class Constructor.
	 *
	 * @param $tleString    string    TLE Data.
	 * @param $dateTime		array	  DateTime array as result from getdate() function.
	 *
	 * @throws \Exception
	 */
	public function __construct($tleString, $dateTime = null) {
		$this->dateTimeZone    = new \DateTimeZone('UTC');
		$this->currentDateTime = ($dateTime == null) ? getdate() : $dateTime;

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
		$this->revTime                     = $this->meanMotion * \Constant::SIDERAL_DAY_SEC;
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

}
