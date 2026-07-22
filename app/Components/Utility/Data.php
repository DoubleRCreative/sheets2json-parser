<?php

namespace App\Components\Utility;

use DateTime;

/**
 * Helpful methods to manipulate and transform data values
 *
 */
class Data
{
    /**
     * Recursive cast to array
     * Loop through provided data array and try to decode any strings as JSON
     * 
     * @param array $data
     * @return array
     */
    public static function toArrayRecursive(array $data): array
    {
        // Loop through each key/value pair
        foreach ($data as $key => $value) {
            // If value is an array, rerun method again
            if (is_array($value)) {
                $data[$key] = self::toArrayRecursive($value);
            } else {
                // If it is not a string, continue (bool/int)
                if (!is_string($value)) continue;
                // JSON decode value (into array) with encoding substitution,
                // If value is not json string, just do a traditional string encoding conversion
                $data[$key] = json_decode(json: $value, associative: true, flags: JSON_INVALID_UTF8_SUBSTITUTE) ?? mb_convert_encoding($value, 'UTF-8');
            }
        }
        return $data;
    }

    /**
     * Parse comma separated string into array
     *
     * @param string $str
     * @return array
     */
    public static function commaSeparatedArray(string $str): array
    {
        $arr = explode(",", $str);
        $arr = array_map('trim', $arr);
        return $arr;
    }

    /**
     * Try to determine the closest schema type of the given data
     *
     * @param mixed $data
     * @return string
     */
    public static function determineSchemaType($data)
    {
        if (is_null($data) || empty($data)) {
            return null;
        }
        if (is_int($data) || is_float($data) || is_numeric($data)) {
            return 'float';
        }
        if (is_bool($data)) {
            return 'boolean';
        }
        if (is_array($data)) {
            return 'array';
        }
        if (self::isCommonDateFormat($data)) {
            return 'date';
        }
        return 'varchar';
    }

    /**
     * Determine the most common schema type across
     * all keys in a given array of arrays
     *
     * @param array $data
     * @param int $max - Maximum number of data items to iterate over
     * @return array
     */
    public static function getCommonSchemaTypes(array $data, int $max = 100): array
    {
        $schema = [];
        $data = array_slice($data, 0, $max);
        // Count types for each key
        foreach ($data as $subArray) {
            if (!is_array($subArray)) continue;
            foreach ($subArray as $key => $value) {
                $type = self::determineSchemaType($value);
                if (is_null($type)) continue; // If type is null, skip
                if (!isset($schema[$key])) {
                    $schema[$key] = []; // Initialize key if not already set
                }
                if (!isset($schema[$key][$type])) {
                    $schema[$key][$type] = 0; // Initialize type count for key
                }
                $schema[$key][$type]++;
            }
        }

        // Determine the most common type for each key
        $commonSchema = [];
        foreach ($schema as $key => $types) {
            arsort($types); // Sort types by count in descending order
            $type = array_key_first($types);
            $commonSchema[$key] = !empty($type) ? $type : null; // Get the most common type
        }

        // Output the results
        return $commonSchema;
    }

    /**
     * Check for common date format, and validate if is parsable date
     *
     * @param string $date
     * @return bool
     */
    public static function isCommonDateFormat(string $date): bool
    {
        $patterns = [
            '/^\d{1,2}-\d{1,2}-\d{2,4}$/', // DD-MM-YYYY
            '/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', // MM/DD/YYYY
        ];

        // Check if the string matches any of the patterns
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $date)) {
                // Verify if it's a valid date
                try {
                    $format = str_contains($pattern, '-') ? 'Y-m-d' : (str_contains($pattern, '/') ? 'm/d/Y' : 'd-m-Y');
                    $parsedDate = DateTime::createFromFormat($format, $date);
                    return !empty($parsedDate) ? true : false;
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Unset array keys
     * 
     * @param array $data Source data array
     * @param array $keys Keys to be removed from the source array
     * @return array
     */
    public static function unsetArrayKeys(array $data, array $keys = []): array
    {
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    /**
     * Search array for specific key
     *
     * @param array $array - search array
     * @param string $targetKey - desired key to look for in the search array
     * @param array $results
     * @return array
     */
    public static function searchArrayByKey(array $array, string $targetKey, &$results = []): array
    {
        foreach ($array as $key => $value) {
            if ($key === $targetKey) {
                $results[] = $value;
            }

            if (is_array($value)) {
                self::searchArrayByKey($value, $targetKey, $results);
            }
        }
        return $results;
    }
}
