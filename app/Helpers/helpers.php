<?php

if (!function_exists('amount_in_words')) {
    function amount_in_words($number)
    {
        $no = floor($number);
        $decimal = round($number - $no, 2) * 100;

        $digits_length = strlen($no);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];

        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number_part = $no % $divider;
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;

            if ($number_part) {
                $plural = ($counter = count($str)) && $number_part > 9 ? '' : '';
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : '';

                if ($number_part < 21) {
                    $str[] = $words[$number_part] . " " . $digits[$counter] . $plural . " " . $hundred;
                } else {
                    $str[] = $words[floor($number_part / 10) * 10]
                           . " " . $words[$number_part % 10]
                           . " " . $digits[$counter] . $plural . " " . $hundred;
                }
            } else {
                $str[] = null;
            }
        }

        $rupees = implode('', array_reverse($str));
        $paise = '';
        if ($decimal > 0) {
            $decimal = (int) $decimal;
            if ($decimal < 21) {
                $paise_words = $words[$decimal];
            } else {
                $tens = $words[floor($decimal / 10) * 10];
                $ones = $words[$decimal % 10];
                $paise_words = trim($tens . ' ' . $ones);
            }
            $paise = " and " . $paise_words . " Paise";
        }

        return trim($rupees) . " Rupees" . $paise . " Only";
    }
}

if (!function_exists('ensure_utf8')) {
    function ensure_utf8(mixed $value): mixed
    {
        if (is_string($value)) {
            if (mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }

            // Convert Windows-1252 / ISO-8859-1 strings (e.g. smart quotes, em-dashes, bullets)
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if (mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }

            // Fallback transcode/scrub invalid bytes
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $cleanKey = is_string($k) ? ensure_utf8($k) : $k;
                $clean[$cleanKey] = ensure_utf8($v);
            }
            return $clean;
        }

        return $value;
    }
}

if (!function_exists('avatar_initial')) {
    function avatar_initial(?string $name, string $fallback = '?'): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $fallback;
        }

        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }
}

if (!function_exists('utf8_json_response')) {
    function utf8_json_response(mixed $data = [], int $status = 200, array $headers = []): \Illuminate\Http\JsonResponse
    {
        $headers = array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers);
        $cleanData = ensure_utf8($data);

        return response()->json(
            $cleanData,
            $status,
            $headers,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}

