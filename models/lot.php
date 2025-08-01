<?php

declare(strict_types=1);

require_once 'helpers.php';
require_once 'init.php';

/**
 * Возвращает список лотов
 *
 * @param $link mysqli Ресурс соединения
 * @return array Список новых лотов в формате ассоциативного массива
 */
function get_new_lots($link): array
{
    $sql = <<<QUERY
        SELECT l.id, l.name, l.start_price, l.img, l.date_end, c.name as category_name FROM lots l
        JOIN categories c ON l.category_id = c.id
        ORDER BY l.created_at DESC LIMIT 6
    QUERY;

    return get_arr($link, $sql);
}

/**
 * Возвращает список лотов по ID
 *
 * @param $link mysqli Ресурс соединения
 * @param int $id ID лота
 * @return array Список лотов по ID в формате ассоциативного массива
 */
function get_lot_by_id($link, $id): array
{
    $sql = <<<QUERY
        SELECT l.*, c.name as category_name FROM lots l
        JOIN categories c ON l.category_id = c.id
        WHERE l.id = ?
    QUERY;
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

/**
 * Добавляет лот и ID к нему
 *
 * @param $link mysqli Ресурс соединения
 * @param array $lot Лот
 * @return $lot_id ID для нового лота
 */
function add_lot($link, $lot)
{
    $lot['user_id'] = $_SESSION['user']['id'];
    $sql = <<<QUERY
        INSERT INTO lots (name, description, start_price, bet_step, date_end, category_id, img, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    QUERY;

    $stmt = db_get_prepare_stmt($link, $sql, $lot);
    $res = mysqli_stmt_execute($stmt);

    if ($res) {
        $lot_id = mysqli_insert_id($link);
        header("Location: lot.php?id=" . $lot_id);
        exit;
    }
    die (mysqli_error($link));
}

/**
 * Возвращает список лотов по поиску
 *
 * @param $link mysqli Ресурс соединения
 * @param string $search Текст из поля поиска
 * @param int $page_items Кол-во записей в результате
 * @param int $offset Смещение выборки
 * @return array Список новых лотов в формате ассоциативного массива
 */
function search_lot($link, $search, $page_items, $offset): array
{
    $sql = <<<QUERY
        SELECT l.id, l.name, l.description, l.start_price, l.img, l.date_end, b.price, c.name as category_name FROM lots l
        JOIN categories c ON l.category_id = c.id
        LEFT JOIN bets b ON l.id = b.lot_id
        WHERE MATCH(l.name, l.description) AGAINST(?)
        ORDER BY l.created_at DESC LIMIT ? OFFSET ?
    QUERY;

    $stmt = db_get_prepare_stmt($link, $sql, [$search, $page_items, $offset]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

/**
 * Возвращает список лотов по категории
 *
 * @param $link mysqli Ресурс соединения
 * @param int $id ID категории
 * @param int $page_items Кол-во записей в результате
 * @param int $offset Смещение выборки
 * @return array Список новых лотов в формате ассоциативного массива
 */
function get_lots_by_category($link, $id, $page_items, $offset): array
{
    $sql = <<<QUERY
        SELECT l.*, c.name as category_name FROM lots l
        JOIN categories c ON l.category_id = c.id
        WHERE l.category_id = ?
        ORDER BY l.created_at DESC LIMIT ? OFFSET ?
    QUERY;

    $stmt = db_get_prepare_stmt($link, $sql, [$id, $page_items, $offset]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

/**
 * Подсчитывает кол-во ставок для лота
 *
 * @param $link mysqli Ресурс соединения
 * @param int $id Значение ID лота
 * @return int Кол-во ставок
 */
function count_lots_by_category($link, $id)
{
    $sql = <<<QUERY
        SELECT COUNT(*) as cnt FROM lots
        WHERE category_id = ?
    QUERY;

    $stmt = db_get_prepare_stmt($link, $sql, [$id]);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res)['cnt'];
}

/**
 * Добавляет флаг победителя в лоте
 *
 * @param $link mysqli Ресурс соединения
 * @param int $winner_id ID победителя
 * @param int $lot_id ID лота
 * @return int Добавления ID победителя
 */
function add_winner_on_db($link, $winner_id, $lot_id)
{
    $sql = <<<QUERY
        UPDATE lots SET winner_id=? WHERE id=?
    QUERY;
    $stmt = db_get_prepare_stmt($link, $sql, [$winner_id, $lot_id]);
    $res = mysqli_stmt_execute($stmt);
    if ($res) {
        echo "Обновление прошло успешно.";
        return;
    }
    echo "Ошибка выполнения: " . mysqli_stmt_error($stmt);
}
