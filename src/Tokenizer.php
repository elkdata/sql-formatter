<?php

declare(strict_types=1);

namespace Doctrine\SqlFormatter;

use function array_combine;
use function array_keys;
use function array_map;
use function arsort;
use function assert;
use function count;
use function implode;
use function preg_match;
use function preg_quote;
use function serialize;
use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

final class Tokenizer
{
    /**
     * Reserved words (for syntax highlighting)
     *
     * @var string[]
     */
    private $reserved = [
        'ACCESSIBLE',
        'ACTION',
        'AGAINST',
        'AGGREGATE',
        'ALGORITHM',
        'ALL',
        'ALTER',
        'ANALYSE',
        'ANALYZE',
        'AS',
        'ASC',
        'AUTOCOMMIT',
        'AUTO_INCREMENT',
        'BACKUP',
        'BEGIN',
        'BETWEEN',
        'BINLOG',
        'BOTH',
        'CASCADE',
        'CASE',
        'CHANGE',
        'CHANGED',
        'CHARACTER SET',
        'CHARSET',
        'CHECK',
        'CHECKSUM',
        'COLLATE',
        'COLLATION',
        'COLUMN',
        'COLUMNS',
        'COMMENT',
        'COMMIT',
        'COMMITTED',
        'COMPRESSED',
        'CONCURRENT',
        'CONSTRAINT',
        'CONTAINS',
        'CONVERT',
        'CREATE',
        'CROSS',
        'CURRENT_TIMESTAMP',
        'DATABASE',
        'DATABASES',
        'DAY',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DEFAULT',
        'DEFINER',
        'DELAYED',
        'DELETE',
        'DESC',
        'DESCRIBE',
        'DETERMINISTIC',
        'DISTINCT',
        'DISTINCTROW',
        'DIV',
        'DO',
        'DUMPFILE',
        'DUPLICATE',
        'DYNAMIC',
        'ELSE',
        'ENCLOSED',
        'END',
        'ENGINE',
        'ENGINE_TYPE',
        'ENGINES',
        'ESCAPE',
        'ESCAPED',
        'EVENTS',
        'EXEC',
        'EXECUTE',
        'EXISTS',
        'EXPLAIN',
        'EXTENDED',
        'FAST',
        'FIELDS',
        'FILE',
        'FIRST',
        'FIXED',
        'FLUSH',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GLOBAL',
        'GRANT',
        'GRANTS',
        'GROUP_CONCAT',
        'HEAP',
        'HIGH_PRIORITY',
        'HOSTS',
        'HOUR',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'IDENTIFIED',
        'IF',
        'IFNULL',
        'IGNORE',
        'IN',
        'INDEX',
        'INDEXES',
        'INFILE',
        'INSERT',
        'INSERT_ID',
        'INSERT_METHOD',
        'INTERVAL',
        'INTO',
        'INVOKER',
        'IS',
        'ISOLATION',
        'KEY',
        'KEYS',
        'KILL',
        'LAST_INSERT_ID',
        'LEADING',
        'LEVEL',
        'LIKE',
        'LINEAR',
        'LINES',
        'LOAD',
        'LOCAL',
        'LOCK',
        'LOCKS',
        'LOGS',
        'LOW_PRIORITY',
        'MARIA',
        'MASTER',
        'MASTER_CONNECT_RETRY',
        'MASTER_HOST',
        'MASTER_LOG_FILE',
        'MATCH',
        'MAX_CONNECTIONS_PER_HOUR',
        'MAX_QUERIES_PER_HOUR',
        'MAX_ROWS',
        'MAX_UPDATES_PER_HOUR',
        'MAX_USER_CONNECTIONS',
        'MEDIUM',
        'MERGE',
        'MINUTE',
        'MINUTE_SECOND',
        'MIN_ROWS',
        'MODE',
        'MODIFY',
        'MONTH',
        'MRG_MYISAM',
        'MYISAM',
        'NAMES',
        'NATURAL',
        'NOT',
        'NOW()',
        'NULL',
        'OFFSET',
        'ON',
        'OPEN',
        'OPTIMIZE',
        'OPTION',
        'OPTIONALLY',
        'ON UPDATE',
        'ON DELETE',
        'OUTFILE',
        'PACK_KEYS',
        'PAGE',
        'PARTIAL',
        'PARTITION',
        'PARTITIONS',
        'PASSWORD',
        'PRIMARY',
        'PRIVILEGES',
        'PROCEDURE',
        'PROCESS',
        'PROCESSLIST',
        'PURGE',
        'QUICK',
        'RANGE',
        'RAID0',
        'RAID_CHUNKS',
        'RAID_CHUNKSIZE',
        'RAID_TYPE',
        'READ',
        'READ_ONLY',
        'READ_WRITE',
        'REFERENCES',
        'REGEXP',
        'RELOAD',
        'RENAME',
        'REPAIR',
        'REPEATABLE',
        'REPLACE',
        'REPLICATION',
        'RESET',
        'RESTORE',
        'RESTRICT',
        'RETURN',
        'RETURNS',
        'REVOKE',
        'RLIKE',
        'ROLLBACK',
        'ROW',
        'ROWS',
        'ROW_FORMAT',
        'SECOND',
        'SECURITY',
        'SEPARATOR',
        'SERIALIZABLE',
        'SESSION',
        'SHARE',
        'SHOW',
        'SHUTDOWN',
        'SLAVE',
        'SONAME',
        'SOUNDS',
        'SQL',
        'SQL_AUTO_IS_NULL',
        'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS',
        'SQL_BIG_TABLES',
        'SQL_BUFFER_RESULT',
        'SQL_CALC_FOUND_ROWS',
        'SQL_LOG_BIN',
        'SQL_LOG_OFF',
        'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES',
        'SQL_MAX_JOIN_SIZE',
        'SQL_QUOTE_SHOW_CREATE',
        'SQL_SAFE_UPDATES',
        'SQL_SELECT_LIMIT',
        'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT',
        'SQL_WARNINGS',
        'SQL_CACHE',
        'SQL_NO_CACHE',
        'START',
        'STARTING',
        'STATUS',
        'STOP',
        'STORAGE',
        'STRAIGHT_JOIN',
        'STRING',
        'STRIPED',
        'SUPER',
        'TABLE',
        'TABLES',
        'TEMPORARY',
        'TERMINATED',
        'THEN',
        'TO',
        'TRAILING',
        'TRANSACTIONAL',
        'TRUE',
        'TRUNCATE',
        'TYPE',
        'TYPES',
        'UNCOMMITTED',
        'UNIQUE',
        'UNLOCK',
        'UNSIGNED',
        'USAGE',
        'USE',
        'USING',
        'VARIABLES',
        'VIEW',
        'WHEN',
        'WITH',
        'WORK',
        'WRITE',
        'YEAR_MONTH',
    ];

    /**
     * For SQL formatting
     * These keywords will all be on their own line
     *
     * @var string[]
     */
    private $reservedToplevel = [
        'SELECT',
        'FROM',
        'WHERE',
        'SET',
        'ORDER BY',
        'GROUP BY',
        'LIMIT',
        'DROP',
        'VALUES',
        'UPDATE',
        'HAVING',
        'ADD',
        'AFTER',
        'ALTER TABLE',
        'DELETE FROM',
        'UNION ALL',
        'UNION',
        'EXCEPT',
        'INTERSECT',
    ];

    /** @var string[] */
    private $reservedNewline = [
        'LEFT OUTER JOIN',
        'RIGHT OUTER JOIN',
        'LEFT JOIN',
        'RIGHT JOIN',
        'OUTER JOIN',
        'INNER JOIN',
        'JOIN',
        'XOR',
        'OR',
        'AND',
    ];

    /** @var string[] */
    private $functions = [
        'ABS',
        'ACOS',
        'ADDDATE',
        'ADDTIME',
        'AES_DECRYPT',
        'AES_ENCRYPT',
        'AREA',
        'ASBINARY',
        'ASCII',
        'ASIN',
        'ASTEXT',
        'ATAN',
        'ATAN2',
        'AVG',
        'BDMPOLYFROMTEXT',
        'BDMPOLYFROMWKB',
        'BDPOLYFROMTEXT',
        'BDPOLYFROMWKB',
        'BENCHMARK',
        'BIN',
        'BIT_AND',
        'BIT_COUNT',
        'BIT_LENGTH',
        'BIT_OR',
        'BIT_XOR',
        'BOUNDARY',
        'BUFFER',
        'CAST',
        'CEIL',
        'CEILING',
        'CENTROID',
        'CHAR',
        'CHARACTER_LENGTH',
        'CHARSET',
        'CHAR_LENGTH',
        'COALESCE',
        'COERCIBILITY',
        'COLLATION',
        'COMPRESS',
        'CONCAT',
        'CONCAT_WS',
        'CONNECTION_ID',
        'CONTAINS',
        'CONV',
        'CONVERT',
        'CONVERT_TZ',
        'CONVEXHULL',
        'COS',
        'COT',
        'COUNT',
        'CRC32',
        'CROSSES',
        'CURDATE',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'CURRENT_TIMESTAMP',
        'CURRENT_USER',
        'CURTIME',
        'DATABASE',
        'DATE',
        'DATEDIFF',
        'DATE_ADD',
        'DATE_DIFF',
        'DATE_FORMAT',
        'DATE_SUB',
        'DAY',
        'DAYNAME',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFYEAR',
        'DECODE',
        'DEFAULT',
        'DEGREES',
        'DES_DECRYPT',
        'DES_ENCRYPT',
        'DIFFERENCE',
        'DIMENSION',
        'DISJOINT',
        'DISTANCE',
        'ELT',
        'ENCODE',
        'ENCRYPT',
        'ENDPOINT',
        'ENVELOPE',
        'EQUALS',
        'EXP',
        'EXPORT_SET',
        'EXTERIORRING',
        'EXTRACT',
        'EXTRACTVALUE',
        'FIELD',
        'FIND_IN_SET',
        'FLOOR',
        'FORMAT',
        'FOUND_ROWS',
        'FROM_DAYS',
        'FROM_UNIXTIME',
        'GEOMCOLLFROMTEXT',
        'GEOMCOLLFROMWKB',
        'GEOMETRYCOLLECTION',
        'GEOMETRYCOLLECTIONFROMTEXT',
        'GEOMETRYCOLLECTIONFROMWKB',
        'GEOMETRYFROMTEXT',
        'GEOMETRYFROMWKB',
        'GEOMETRYN',
        'GEOMETRYTYPE',
        'GEOMFROMTEXT',
        'GEOMFROMWKB',
        'GET_FORMAT',
        'GET_LOCK',
        'GLENGTH',
        'GREATEST',
        'GROUP_CONCAT',
        'GROUP_UNIQUE_USERS',
        'HEX',
        'HOUR',
        'IF',
        'IFNULL',
        'INET_ATON',
        'INET_NTOA',
        'INSERT',
        'INSTR',
        'INTERIORRINGN',
        'INTERSECTION',
        'INTERSECTS',
        'INTERVAL',
        'ISCLOSED',
        'ISEMPTY',
        'ISNULL',
        'ISRING',
        'ISSIMPLE',
        'IS_FREE_LOCK',
        'IS_USED_LOCK',
        'LAST_DAY',
        'LAST_INSERT_ID',
        'LCASE',
        'LEAST',
        'LEFT',
        'LENGTH',
        'LINEFROMTEXT',
        'LINEFROMWKB',
        'LINESTRING',
        'LINESTRINGFROMTEXT',
        'LINESTRINGFROMWKB',
        'LN',
        'LOAD_FILE',
        'LOCALTIME',
        'LOCALTIMESTAMP',
        'LOCATE',
        'LOG',
        'LOG10',
        'LOG2',
        'LOWER',
        'LPAD',
        'LTRIM',
        'MAKEDATE',
        'MAKETIME',
        'MAKE_SET',
        'MASTER_POS_WAIT',
        'MAX',
        'MBRCONTAINS',
        'MBRDISJOINT',
        'MBREQUAL',
        'MBRINTERSECTS',
        'MBROVERLAPS',
        'MBRTOUCHES',
        'MBRWITHIN',
        'MD5',
        'MICROSECOND',
        'MID',
        'MIN',
        'MINUTE',
        'MLINEFROMTEXT',
        'MLINEFROMWKB',
        'MOD',
        'MONTH',
        'MONTHNAME',
        'MPOINTFROMTEXT',
        'MPOINTFROMWKB',
        'MPOLYFROMTEXT',
        'MPOLYFROMWKB',
        'MULTILINESTRING',
        'MULTILINESTRINGFROMTEXT',
        'MULTILINESTRINGFROMWKB',
        'MULTIPOINT',
        'MULTIPOINTFROMTEXT',
        'MULTIPOINTFROMWKB',
        'MULTIPOLYGON',
        'MULTIPOLYGONFROMTEXT',
        'MULTIPOLYGONFROMWKB',
        'NAME_CONST',
        'NULLIF',
        'NUMGEOMETRIES',
        'NUMINTERIORRINGS',
        'NUMPOINTS',
        'OCT',
        'OCTET_LENGTH',
        'OLD_PASSWORD',
        'ORD',
        'OVERLAPS',
        'PASSWORD',
        'PERIOD_ADD',
        'PERIOD_DIFF',
        'PI',
        'POINT',
        'POINTFROMTEXT',
        'POINTFROMWKB',
        'POINTN',
        'POINTONSURFACE',
        'POLYFROMTEXT',
        'POLYFROMWKB',
        'POLYGON',
        'POLYGONFROMTEXT',
        'POLYGONFROMWKB',
        'POSITION',
        'POW',
        'POWER',
        'QUARTER',
        'QUOTE',
        'RADIANS',
        'RAND',
        'RELATED',
        'RELEASE_LOCK',
        'REPEAT',
        'REPLACE',
        'REVERSE',
        'RIGHT',
        'ROUND',
        'ROW_COUNT',
        'RPAD',
        'RTRIM',
        'SCHEMA',
        'SECOND',
        'SEC_TO_TIME',
        'SESSION_USER',
        'SHA',
        'SHA1',
        'SIGN',
        'SIN',
        'SLEEP',
        'SOUNDEX',
        'SPACE',
        'SQRT',
        'SRID',
        'STARTPOINT',
        'STD',
        'STDDEV',
        'STDDEV_POP',
        'STDDEV_SAMP',
        'STRCMP',
        'STR_TO_DATE',
        'SUBDATE',
        'SUBSTR',
        'SUBSTRING',
        'SUBSTRING_INDEX',
        'SUBTIME',
        'SUM',
        'SYMDIFFERENCE',
        'SYSDATE',
        'SYSTEM_USER',
        'TAN',
        'TIME',
        'TIMEDIFF',
        'TIMESTAMP',
        'TIMESTAMPADD',
        'TIMESTAMPDIFF',
        'TIME_FORMAT',
        'TIME_TO_SEC',
        'TOUCHES',
        'TO_DAYS',
        'TRIM',
        'TRUNCATE',
        'UCASE',
        'UNCOMPRESS',
        'UNCOMPRESSED_LENGTH',
        'UNHEX',
        'UNIQUE_USERS',
        'UNIX_TIMESTAMP',
        'UPDATEXML',
        'UPPER',
        'USER',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'UUID',
        'VARIANCE',
        'VAR_POP',
        'VAR_SAMP',
        'VERSION',
        'WEEK',
        'WEEKDAY',
        'WEEKOFYEAR',
        'WITHIN',
        'X',
        'Y',
        'YEAR',
        'YEARWEEK',
    ];

    // Regular expressions for tokenizing

    /** @var string */
    private $regexBoundaries;

    /** @var string */
    private $regexReserved;

    /** @var string */
    private $regexReservedNewline;

    /** @var string */
    private $regexReservedToplevel;

    /** @var string */
    private $regexFunction;

    /**
     * Punctuation that can be used as a boundary between other tokens
     *
     * @var string[]
     */
    private $boundaries = [
        ',',
        ';',
        ':',
        ')',
        '(',
        '.',
        '=',
        '<',
        '>',
        '+',
        '-',
        '*',
        '/',
        '!',
        '^',
        '%',
        '|',
        '&',
        '#',
    ];

    // Cache variables
    // Only tokens shorter than this size will be cached.  Somewhere between 10
    // and 20 seems to work well for most cases.
    /** @var int */
    public $maxCachekeySize = 15;

    /** @var Token[] */
    private $tokenCache = [];

    /** @var int */
    private $cacheHits = 0;

    /** @var int */
    private $cacheMisses = 0;

    /**
     * Stuff that only needs to be done once. Builds regular expressions and
     * sorts the reserved words.
     */
    public function __construct()
    {
        // Sort reserved word list from longest word to shortest, 3x faster than usort
        $reservedMap = array_combine($this->reserved, array_map('strlen', $this->reserved));
        assert($reservedMap !== false);
        arsort($reservedMap);
        $this->reserved = array_keys($reservedMap);

        // Set up regular expressions
        $this->regexBoundaries       = '(' . implode(
            '|',
            $this->quoteRegex($this->boundaries)
        ) . ')';
        $this->regexReserved         = '(' . implode(
            '|',
            $this->quoteRegex($this->reserved)
        ) . ')';
        $this->regexReservedToplevel = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedToplevel)
        ) . ')');
        $this->regexReservedNewline  = str_replace(' ', '\\s+', '(' . implode(
            '|',
            $this->quoteRegex($this->reservedNewline)
        ) . ')');

        $this->regexFunction = '(' . implode('|', $this->quoteRegex($this->functions)) . ')';
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param string $string The SQL string
     *
     * @return array<int,Token> An array of tokens.
     */
    public function tokenize(string $string) : array
    {
        $tokens = [];

        // Used to make sure the string keeps shrinking on each iteration
        $oldStringLen = strlen($string) + 1;

        $token = null;

        $currentLength = strlen($string);

        // Keep processing the string until it is empty
        while ($currentLength) {
            // If the string stopped shrinking, there was a problem
            if ($oldStringLen <= $currentLength) {
                $tokens[] = new Token(Token::TOKEN_TYPE_ERROR, $string);

                return $tokens;
            }

            $oldStringLen =  $currentLength;

            // Determine if we can use caching
            if ($currentLength >= $this->maxCachekeySize) {
                $cacheKey = substr($string, 0, $this->maxCachekeySize);
            } else {
                $cacheKey = false;
            }

            // See if the token is already cached
            if ($cacheKey && isset($this->tokenCache[$cacheKey])) {
                // Retrieve from cache
                $token       = $this->tokenCache[$cacheKey];
                $tokenLength = strlen($token->value());
                $this->cacheHits++;
            } else {
                // Get the next token and the token type
                $token       = $this->getNextToken($string, $token);
                $tokenLength = strlen($token->value());
                $this->cacheMisses++;

                // If the token is shorter than the max length, store it in cache
                if ($cacheKey && $tokenLength < $this->maxCachekeySize) {
                    $this->tokenCache[$cacheKey] = $token;
                }
            }

            $tokens[] = $token;

            // Advance the string
            $string = substr($string, $tokenLength);

            $currentLength -= $tokenLength;
        }

        return $tokens;
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation
     * are all their own tokens.
     *
     * @param string     $string   The SQL string
     * @param Token|null $previous The result of the previous getNextToken() call
     *
     * @return Token An associative array containing the type and value of the token.
     */
    private function getNextToken(string $string, ?Token $previous = null) : Token
    {
        $matches = [];
        // Whitespace
        if (preg_match('/^\s+/', $string, $matches)) {
            return new Token(Token::TOKEN_TYPE_WHITESPACE, $matches[0]);
        }

        // Comment
        if ($string[0] === '#' ||
            (isset($string[1]) && ($string[0]==='-' && $string[1]==='-') ||
            ($string[0]==='/' && $string[1]==='*'))) {
            // Comment until end of line
            if ($string[0] === '-' || $string[0] === '#') {
                $last = strpos($string, "\n");
                $type = Token::TOKEN_TYPE_COMMENT;
            } else { // Comment until closing comment tag
                $pos = strpos($string, '*/', 2);
                assert($pos !== false);
                $last = $pos + 2;
                $type = Token::TOKEN_TYPE_BLOCK_COMMENT;
            }

            if ($last === false) {
                $last = strlen($string);
            }

            return new Token($type, substr($string, 0, $last));
        }

        // Quoted String
        if ($string[0]==='"' || $string[0]==='\'' || $string[0]==='`' || $string[0]==='[') {
            return new Token(
                ($string[0]==='`' || $string[0]==='['
                    ? Token::TOKEN_TYPE_BACKTICK_QUOTE
                    : Token::TOKEN_TYPE_QUOTE),
                $this->getQuotedString($string)
            );
        }

        // User-defined Variable
        if (($string[0] === '@' || $string[0] === ':') && isset($string[1])) {
            $value = null;
            $type  = Token::TOKEN_TYPE_VARIABLE;

            // If the variable name is quoted
            if ($string[1]==='"' || $string[1]==='\'' || $string[1]==='`') {
                $value = $string[0] . $this->getQuotedString(substr($string, 1));
            } else {
                // Non-quoted variable name
                preg_match('/^(' . $string[0] . '[a-zA-Z0-9\._\$]+)/', $string, $matches);
                if ($matches) {
                    $value = $matches[1];
                }
            }

            if ($value !== null) {
                return new Token($type, $value);
            }
        }

        // Number (decimal, binary, or hex)
        if (preg_match(
            '/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|' . $this->regexBoundaries . ')/',
            $string,
            $matches
        )) {
            return new Token(Token::TOKEN_TYPE_NUMBER, $matches[1]);
        }

        // Boundary Character (punctuation and symbols)
        if (preg_match('/^(' . $this->regexBoundaries . ')/', $string, $matches)) {
            return new Token(Token::TOKEN_TYPE_BOUNDARY, $matches[1]);
        }

        // A reserved word cannot be preceded by a '.'
        // this makes it so in "mytable.from", "from" is not considered a reserved word
        if (! $previous || $previous->value() !== '.') {
            $upper = strtoupper($string);
            // Top Level Reserved Word
            if (preg_match(
                '/^(' . $this->regexReservedToplevel . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED_TOPLEVEL,
                    substr($string, 0, strlen($matches[1]))
                );
            }

            // Newline Reserved Word
            if (preg_match(
                '/^(' . $this->regexReservedNewline . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED_NEWLINE,
                    substr($string, 0, strlen($matches[1]))
                );
            }

            // Other Reserved Word
            if (preg_match(
                '/^(' . $this->regexReserved . ')($|\s|' . $this->regexBoundaries . ')/',
                $upper,
                $matches
            )) {
                return new Token(
                    Token::TOKEN_TYPE_RESERVED,
                    substr($string, 0, strlen($matches[1]))
                );
            }
        }

        // A function must be suceeded by '('
        // this makes it so "count(" is considered a function, but "count" alone is not
        $upper = strtoupper($string);
        // function
        if (preg_match('/^(' . $this->regexFunction . '[(]|\s|[)])/', $upper, $matches)) {
            return new Token(
                Token::TOKEN_TYPE_RESERVED,
                substr($string, 0, strlen($matches[1])-1)
            );
        }

        // Non reserved word
        preg_match('/^(.*?)($|\s|["\'`]|' . $this->regexBoundaries . ')/', $string, $matches);

        return new Token(Token::TOKEN_TYPE_WORD, $matches[1]);
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param string[] $strings The strings to be quoted
     *
     * @return string[] The quoted strings
     */
    private function quoteRegex(array $strings) : array
    {
        return array_map(static function (string $string) : string {
            return preg_quote($string, '/');
        }, $strings);
    }

    /**
     * Get stats about the token cache
     *
     * @return mixed[] An array containing the keys 'hits', 'misses', 'entries', and 'size' in bytes
     */
    public function getCacheStats() : array
    {
        return [
            'hits' => $this->cacheHits,
            'misses' => $this->cacheMisses,
            'entries' => count($this->tokenCache),
            'size' => strlen(serialize($this->tokenCache)),
        ];
    }

    private function getQuotedString(string $string) : string
    {
        $ret = '';

        // This checks for the following patterns:
        // 1. backtick quoted string using `` to escape
        // 2. square bracket quoted string (SQL Server) using ]] to escape
        // 3. double quoted string using "" or \" to escape
        // 4. single quoted string using '' or \' to escape
        if (preg_match(
            '/^(((`[^`]*($|`))+)|
            ((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|
            (("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|
            ((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/sx',
            $string,
            $matches
        )) {
            $ret = $matches[1];
        }

        return $ret;
    }
}
