<?php
/**
 * Tutaj znajdują się klasa Database odpowiadająca za obsługę bazy danych.
 */

namespace wkakis;

use PDO
;

/**
 * Obsługa kontrolera bazy danych.
 */
class Database
{
    /**
     * @var PDO Uchwyt dla sterownika PDO.
     */
    private $PDO;
    /**
     * Konstruktor bazy danych, przyjmuje konfiguracje połączenia z pliku wkakis.config.php.
     * @param string[] $config Konfiguracja bazy danych.
     *     + **string** - $config['host']-  Adres IP/domena serwera bazy danych.
     *     + **string** - $config['port']-  Port serwera bazy danych. Domyślnie 3306.
     *     + **string** - $config['dbname'] -  Nazwa bazy danych.
     *     + **string** - $config['charset'] -  Kodowanie znaków, najlepiej utf8.
     *     + **string** - $config['user'] -  Nazwa użytkownika.
     *     + **string** - $config['password'] - Hasło.
     */
    public function __construct($config = array())
    {
        try {
            $dsn = 'mysql:host='.$config['host'].';dbname='.$config['dbname'].';charset='.$config['charset'];
            $options = array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            );
            $this->PDO = new PDO($dsn, $config['user'], $config['password'], $options);
        } catch (PDOException $e) {
            print_r($e);
            //TODO: error handling
        }
    }

    /**
     * Wykonuje surowe zapytanie do bazy danych (brak obsługi 'param binding').
     * @param  string $query Zapytanie SQL.
     * @return PDOStatement Obiekt klasy PDOStatement.
     */
    public function query($query = '')
    {
        return $this->PDO->query($query);
    }

    /**
     * Wykonuje surowe zapytanie do bazy danych (tak jak metoda query()) i dodatkowo pobiera wartości w postaci tablicy asocjacyjnej.
     * @param  string $query Zapytanie SQL.
     * @return array        Tablica asocjacyna zawierająca wynikowe rekordy. W przypadku poleceń nie wybierających rekordów z bazy funkcja zwraca pustą tablice.
     */
    public function queryAndFetch($query = '')
    {
        return $this->query($query)->fetchAll();
    }

    /**
     * Przygotowuje i wykonuje zapytanie SQL. Obsługuje opcjonalne bindowanie parametrów w przypadku dodania tablicy przypisań jako drugiego argumentu.
     * @param  string $query  Zapytanie SQL. Może zawierać bindowanie parametrów  przypadku podania tablicy przypisań jako drugiego argumentu.
     * @param  array  $params Tablica przypisań (może być indexowana lub asocjacyjna).
     * @return PDOStatement   Obiekt PDOStatement dla późniejszego użytku.
     */
    public function prepareAndExecute($query, $params = array())
    {
        try {
            if(count($params)) {
                $stmt = $this->PDO->prepare($query);
                $stmt->execute($params);
            } else {
                $stmt = $this->PDO->query($query);
            }
            return $stmt;
        } catch (PDOException $e) {
            return false;
        }
    }
    /**
     * Tworzy dopełnienie zapytania SQL poprzez warunki przekazane w tablicy. Pozwalana na zachowanie poprawnej kolejnosci warunków oraz filtruje według tablicy $queryCompletions.
     * ```php
     * $queryCompletions = array(
     *     'where'=> ' WHERE ',
     *     'group'=> ' GROUP BY ',
     *     'having'=> ' HAVING ',
     *     'order'=> ' ORDER BY ',
     *     'limit'=> ' LIMIT '
     * );
     *
     * $options = array(
     *     'select'=> '*',
     *     'from'=> 'table',
     *     'where'=> 'column=5',
     *     'group'=> 'column2',
     *     'order'=> 'id DESC',
     *     'limit'=> 21
     * );
     *
     * $db = new Database($dbconfig);
     *
     * echo $db->prepareAndExecute($options, $queryCompletions);
     * // 'WHERE column=5 GROUP BY column2 ORDER BY id desc LIMIT 21'
     * ```
     * @param  string[] $options          Opcje zapytania SQL z możliwymi dopełnieniami.
     * @param  string[] $queryCompletions Asocjacyjna tablica dopełnień.
     * @return string                     Dopełnienie do zapytania SQL.
     */
    protected function completeQuery($options, $queryCompletions)
    {
        $query = '';
        foreach ($queryCompletions as $queryCompletion => $q) {
            if (isset($options[$queryCompletion])) {
                $query.=$q.$options[$queryCompletion];
            }
        }
        return $query;
    }
    /**
     * Wykonuje zapytanie typu SELECT.
     * @link https://dev.mysql.com/doc/refman/5.7/en/select.html Dokumentacja polecenia SELECT.
     * @param  int[]|string[] $options Tablica asocjacyjna z opcjami zapytania SELECT.
     *     + **string** - $config['select']-  Nazwy kolumn.
     *     + **string** - $config['from']-  Nazwa tabeli z której pobieramy
     *     + **string** - $config['where'] -  Warunek where polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['having'] -  Warunek having polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['order'] -  Warunek order polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['limit'] -  Warunek limit polecenia SQL (sprawdz dokumentacje).
     * @param  array  $params  Opcjonalna tablica indexowana z parametrami dla poleceń prepared statements.
     * @return mixed[]         Tablica asocjacyjna zawierajaca rekordy tabeli.
     */
    public function select($options, $params = array())
    {
        $query = 'SELECT '.$options['select'].' FROM '.$options['from'];
        $queryCompletions = array(
            'where'=> ' WHERE ',
            'group'=> ' GROUP BY ',
            'having'=> ' HAVING ',
            'order'=> ' ORDER BY ',
            'limit'=> ' LIMIT '
        );
        $query .= $this->completeQuery($options, $queryCompletions);
        return $this->prepareAndExecute($query, $params)->fetchAll();
    }

    /**
     * Wykonuje zapytanie typu UPDATE.
     * ```php
     * $db = new Database($dbconfig);
     * $db->update(array(
     *     'update'=> 'table',
     *     'set'=> array(
     *         'column1'=> 'value',
     *         'column2'=> 37
     *     ),
     *     'where'=> 'id=5'
     * ));
     * ```
     * @link https://dev.mysql.com/doc/refman/5.7/en/update.html Dokumentacja polecenia UPDATE.
     * @param  int[]|string[] $options Tablica asocjacyjna z opcjami zapytania UPDATE.
     *     + **string** - $config['update']-  Nazwa tablicy.
     *     + **string[]** - $config['set']-  Tablica asocjacyjna zawierająca przypisania pól oraz ich odpowiednich wartości.
     *     + **string** - $config['where'] -  Warunek where polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['order'] -  Warunek order polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['limit'] -  Warunek limit polecenia SQL (sprawdz dokumentacje).
     * @param  array  $params  Opcjonalna tablica indexowana z parametrami dla poleceń prepared statements.
     * @return PDOStatement         Obiekt PDOStatement dla późniejszego użytku.
     */
    public function update($options, $params = array())
    {
        $query = 'UPDATE '.$options['update'].' SET ';
        $query .= implode(', ', array_map(function($key) {
            return "`$key`=?";
        }, array_keys($options['set'])));
        $queryCompletions = array(
            'where'=> ' WHERE ',
            'order'=> ' ORDER BY ',
            'limit'=> ' LIMIT '
        );
        $query .= $this->completeQuery($options, $queryCompletions);
        $params = array_merge(array_values($options['set']), $params);
        return $this->prepareAndExecute($query, $params);
    }

    /**
     * Wykonuje zapytanie typu DELETE.
     * ```php
     * $db = new Database($dbconfig);
     * $db->delete(array(
     *     'from'=> 'table',
     *     'where'=> 'id=5'
     * ));
     * ```
     * @link https://dev.mysql.com/doc/refman/5.7/en/delete.html Dokumentacja polecenia DELETE.
     * @param  int[]|string[] $options Tablica asocjacyjna z opcjami zapytania DELETE.
     *     + **string** - $config['from']-  Nazwa tablicy.
     *     + **string** - $config['where'] -  Warunek where polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['order'] -  Warunek order polecenia SQL (sprawdz dokumentacje).
     *     + **string** - $config['limit'] -  Warunek limit polecenia SQL (sprawdz dokumentacje).
     * @param  array  $params  Opcjonalna tablica indexowana z parametrami dla poleceń prepared statements.
     * @return PDOStatement    Obiekt PDOStatement dla późniejszego użytku.
     */
    public function delete($options, $params = array())
    {
        $query = 'DELETE FROM '.$options['from'];
        $queryCompletions = array(
            'where'=> ' WHERE ',
            'order'=> ' ORDER BY ',
            'limit'=> ' LIMIT '
        );
        $query .= $this->completeQuery($options, $queryCompletions);
        return $this->prepareAndExecute($query, array_merge(array_values($options['set']), $params));
    }

    /**
     * Wykonuje zapytanie typu INSERT.
     * ```php
     * $db = new Database($dbconfig);
     * $db->insert(array(
     *     'into'=> 'table',
     *     'values'=> array(
     *         'column1'=>'value1',
     *         'column2'=>'value2',
     *         'column3'=> 3
     *     )
     * ));
     * ```
     * @link https://dev.mysql.com/doc/refman/5.7/en/insert.html Dokumentacja polecenia INSERT.
     * @param  int[]|string[] $options Tablica asocjacyjna z opcjami zapytania INSERT.
     *     + **string** - $config['into']-  Nazwa tablicy.
     *     + **string** - $config['values']-  Tablica asocjacyjna z wprowadzanymi polami.
     * @return PDOStatement    Obiekt PDOStatement dla późniejszego użytku.
     */
    public function insert($options) {
        $query = 'INSERT INTO '.$options['into'];
        $query .= '('.implode(', ', array_map(function($key) {
            return "`$key`";
        }, array_keys($options['values']))).')';
        $query.= 'VALUES ('.implode(', ', array_map(function($key) {
            return '?';
        }, array_values($options['values']))).')';

        return $this->prepareAndExecute($query, array_values($options['values']));
    }
}
