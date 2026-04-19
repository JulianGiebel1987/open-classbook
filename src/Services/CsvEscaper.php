<?php

namespace OpenClassbook\Services;

/**
 * Schutz vor CSV-Formel-Injection (OWASP "CSV Injection").
 * Zellinhalte, die mit =, +, -, @, Tab oder Carriage-Return beginnen,
 * werden von Tabellenkalkulationen (Excel, LibreOffice, Google Sheets)
 * als Formeln interpretiert und koennen bei manipulierten Daten
 * beliebigen Code ausfuehren oder Daten exfiltrieren.
 * Loesung: Ein fuehrendes Apostroph (') neutralisiert die Formel.
 */
class CsvEscaper
{
    /**
     * Einzelne Zelle absichern.
     */
    public static function escape($value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string) $value;
        if ($str === '') {
            return $str;
        }
        $first = $str[0];
        if ($first === '=' || $first === '+' || $first === '-' || $first === '@' || $first === "\t" || $first === "\r") {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Komplette Zeile absichern.
     */
    public static function escapeRow(array $row): array
    {
        return array_map([self::class, 'escape'], $row);
    }
}
