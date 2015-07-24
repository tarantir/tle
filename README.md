# TLE - Two Line Element
NASA/NORAD Two line element set framework

A two-line element (TLE) is a set of two data lines listing orbital elements that describe the state (position and velocity) of an Earth-orbiting object. The TLE data representation is specific to the Simplified perturbations models (SGP, SGP4, SDP4, SGP8 and SDP8) so any algorithm using a TLE as a data source must implement one of the simplified perturbations models to correctly compute the state of an object at a time of interest.

Parser usage:
```
require_once('TLE/Parser.php');

$tleParser = new Parser($tleString);

$tleParser->eccentricity;
$tleParser->raan;

```
