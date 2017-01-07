<?php namespace Zephyrus\Application;

class Formatter
{
    public static function formatElapsedDateTime($dateTime)
    {
        $inFrench = strpos(Configuration::getApplicationConfiguration('locale'), 'fr') !== false;
        $terms = [
            'fr' => ['YESTERDAY' => 'Hier, ', 'TODAY' => 'Aujourd\'hui, '],
            'en' => ['YESTERDAY' => 'Yesterday, ', 'TODAY' => 'Today, ']
        ];
        $lang = ($inFrench) ? $terms['fr'] : $terms['en'];

        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        $now = new \DateTime();
        $diff = $dateTime->diff($now);
        if ($diff->d == 0) {
            if ($diff->h == 0) {
                if ($diff->i == 0) {
                    return ($inFrench) ?
                        ("Il y a " . $diff->s . " seconde" . (($diff->s > 1) ? 's' : '')) :
                        ($diff->s . " second" . (($diff->s > 1) ? 's' : '') . ' ago');
                }
                return ($inFrench) ?
                    ("Il y a " . $diff->i . " minute" . (($diff->i > 1) ? 's' : '')) :
                    ($diff->i . " minute" . (($diff->i > 1) ? 's' : '') . ' ago');
            }
            return $lang['TODAY'] . self::formatTime($dateTime);
        } elseif ($diff->d == 1 && $diff->h == 0) {
            return $lang['YESTERDAY'] . self::formatTime($dateTime);
        }
        return self::formatDateTime($dateTime);
    }

    public static function formatDate($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'date'), $dateTime->getTimestamp());
    }

    public static function formatDateTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'datetime'), $dateTime->getTimestamp());
    }

    public static function formatTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'time'), $dateTime->getTimestamp());
    }

    public static function formatPercent($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        return $result;
    }

    public static function formatMoney($amount, $minDecimals = 2, $maxDecimals = 2)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $result = $formatter->formatCurrency($amount, Configuration::getApplicationConfiguration('currency'));
        return $result;
    }

    public static function formatDecimal($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        return $result;
    }

    /**
     * Returns a SEO compatible url based on the specified string. Be sure to check the LC_CTYPE locale setting if
     * getting any question marks in result. Run locale -a on server to see full list of supported locales.
     *
     * @param string $name
     * @return string
     */
    function formatSeoUrl($name)
    {
        $url = strtolower($name);
        $url = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $url);
        $url = preg_replace("/[^a-z0-9_\s-]/", "", $url);
        $url = preg_replace("/[\s-]+/", " ", $url);
        $url = trim($url);
        return preg_replace("/[\s_]/", "-", $url);
    }

    /**
     * http://stackoverflow.com/questions/15188033/human-readable-file-size
     * @param int $sizeInBytes
     * @return string
     */
    public static function formatHumanFileSize($sizeInBytes)
    {
        $terms = [
            'fr' => ['G' => 'go', 'M' => 'mo', 'K' => 'ko', 'B' => 'octets'],
            'en' => ['G' => 'gb', 'M' => 'mb', 'K' => 'kb', 'B' => 'bytes']
        ];
        $lang = (strpos(Configuration::getApplicationConfiguration('locale'), 'fr') !== false) ? $terms['fr'] : $terms['en'];
        if ($sizeInBytes >= 1073741824) {
            $fileSize = round($sizeInBytes / 1024 / 1024 / 1024, 1);
            $unit = $lang['G'];
        } elseif ($sizeInBytes >= 1048576) {
            $fileSize = round($sizeInBytes / 1024 / 1024, 1);
            $unit = $lang['M'];
        } elseif($sizeInBytes >= 1024) {
            $fileSize = round($sizeInBytes / 1024, 1);
            $unit = $lang['K'];
        } else {
            $fileSize = $sizeInBytes;
            $unit = $lang['B'];
        }
        return self::formatDecimal($fileSize, 0, 2) . ' ' . $unit;
    }
}