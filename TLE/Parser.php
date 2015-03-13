<?php

namespace TLE;

class Parser {

	const ORBIT_PROGRADE = 1;
	const ORBIT_RETROGRADE = 2;

	/**
	 * @var string
	 *
	 * Twenty-four character name (to be consistent with the name length in the NORAD SATCAT).
	 */
	public $name = 'NaN';

	public $satelliteNumber = 'NaN';
	public $classification = 'NaN';
	public $internationalDesignatorLaunchYear = 'NaN';
	public $internationalDesignatorLaunchNumber = 'NaN';
	public $internationalDesignatorLaunchPiece = 'NaN';
	public $epochYear = 'NaN';

	/**
	 *
		A TLE's Epoch indicates the UTC time when the TLE's indicated orbit elements were true.
		The first two digits indicate the Epoch Year. "57" to "99" indicates the years 1957 to 1999 respectively. "00" to "56" indicates the years 2000 to 2056 respectively.
		The next three digits indicate the epoch's integer day, which ranges from 001 to 365 in a standard year or 001 to 366 in a leap year.
		The remaining digits indicate the decimal (fraction) days.

		For the ISS TLE Epoch (06052.34767361):

		"06" = 2006;
		"052" = February 21; and
		".34767361" = 08:20:39 U.T.C.

		This ISS TLE's epoch is therefore 08:20:39 U.T.C. February 21, 2006.
	 *
	 * @var string
	 */
	public $epoch;
	public $epochDay = 'NaN';
	public $epochFraction = 'NaN';
	public $epochUnixTimestamp = 'NaN';

	public $meanMotionFirstDerivate = 'NaN';
	public $meanMotionSecondDerivate = 'NaN';
	public $bstarDragTerm = 'NaN';
	public $ephemerisType = 'NaN';
	public $elementNumber = 'NaN';


	public $inclination = 'NaN';
	public $raan = 'NaN';
	public $eccentricity = 'NaN';
	public $argumentPergee = 'NaN';
	public $meanAnomaly = 'NaN';
	public $meanMontion = 'NaN';
	public $revolutionNumber = 'NaN';

	public function __construct($tleString) {
		$this->dateTimeZone = new \DateTimeZone("UTC");

		$lines = explode("\n", $tleString);

		switch(count($lines)) {
			case 2:

				break;
			case 3:
				$this->name = substr($lines[0], 0, 24);
				unset($lines[0]);
				break;
			default:
				throw new \Exception("Invalid two element set");
		}

		$line1 = reset($lines);
		$line2 = end($lines);

		$this->satelliteNumber = substr($line1, 2, 6);
		$this->classification  = substr($line1, 7, 1);
		$this->internationalDesignatorLaunchYear   = trim(substr($line1, 9, 2));

		if($this->internationalDesignatorLaunchYear) {
			$dateTime = \DateTime::createFromFormat('y', $this->internationalDesignatorLaunchYear);
			$dateTime->setTimezone($this->dateTimeZone);
			$this->internationalDesignatorLaunchYear = $dateTime->format('Y');
		}

		$this->internationalDesignatorLaunchNumber = trim(substr($line1, 12, 2));
		$this->internationalDesignatorLaunchPiece  = trim(substr($line1, 14, 2));

		$this->epochYear                           = trim(substr($line1, 18, 2));
		$dateTime = \DateTime::createFromFormat('y', $this->epochYear);
		$dateTime->setTimezone($this->dateTimeZone);
		$this->epochYear = $dateTime->format('Y');
		$this->epoch                               = trim(substr($line1, 20, 12));
		$epoch = explode('.', $this->epoch);
		$this->epochDay = $epoch[0];
		$this->epochFraction = '0.' . $epoch[1];
		$seconds  = round(86400 * $this->epochFraction);
		$date = new \DateTime();
		$date->setTimezone($this->dateTimeZone);
		$date->setDate($this->epochYear, 1, 1);
		$date->setTime(0, 0, 0);
		$this->epochUnixTimestamp = $date->format('U') + (86400 * $this->epochDay) + $seconds - 86400;

		$this->meanMotionFirstDerivate             = '0' . trim(substr($line1, 33, 10));
		$this->meanMotionSecondDerivate            = trim(substr($line1, 44, 8));
		$this->bstarDragTerm                       = trim(substr($line1, 53, 8));
		$this->ephemerisType                       = trim(substr($line1, 63, 64));
		$this->elementNumber                       = trim(substr($line1, 64, 4));

		$this->satelliteNumber  = trim(substr($line2, 2, 6));
		$this->inclination      = trim(substr($line2, 8, 8));
		$this->raan             = trim(substr($line2, 17, 8));
		$this->eccentricity     = '0.' . trim(substr($line2, 26, 7));
		$this->argumentPergee   = trim(substr($line2, 34, 8));
		$this->meanAnomaly      = trim(substr($line2, 43, 8));
		$this->meanMontion      = trim(substr($line2, 52, 11));
		$this->revolutionNumber = trim(substr($line2, 63, 5));

		$checkSumLine1 = trim(substr($line1, 69));

		$checkSumLine1 = trim(substr($line2, 69));

	}

	function getDateFromDay($day, $year) {
		$date = \DateTime::createFromFormat('z Y', strval($day) . ' ' . strval($year));
		$date->setTimezone($this->dateTimeZone);
		return $date;
	}
}
