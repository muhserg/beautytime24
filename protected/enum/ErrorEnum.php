<?php

/**
 * Константы ошибок
 */
interface ErrorEnum
{
    const TEMPLATE_NOT_EXIST_FOR_USER_TYPE = 'Страница не существует для данного типа пользователя.';

    const USER_NOT_FOUND = 'Пользователь не найден.';
    const ACCESS_DENIED = 'Доступ закрыт.';

    const NOT_FOUND_FOR_USER_TYPE = 'Не существует для данного типа пользователя.';
}
