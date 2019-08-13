<?php

defined('DEV_MODE') || define('DEV_MODE', true);
defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);
defined('REPOSITORY_PATH') || define('REPOSITORY_PATH', realpath(__DIR__ . '/../'));
defined('YII_PATH_RUNTIME') || define('YII_PATH_RUNTIME', REPOSITORY_PATH . '/runtime');
defined('COOKIE_EXPIRES') || define('COOKIE_EXPIRES', time() + 60 * 60 * 24 * 30);

defined('SITE_NAME') || define('SITE_NAME', 'BeautyTime24');
defined('SITE_NAME_RUS') || define('SITE_NAME_RUS', 'БьюитиТайм24');
defined('SERVICE_DESC_PHONE') || define('SERVICE_DESC_PHONE', '+7(926)284-56-01');
defined('SITE_HOST') || define('SITE_HOST', 'http://www.' . SITE_NAME . '.ru');
defined('ADMIN_EMAIL1') || define('ADMIN_EMAIL1', 'muh.maza@gmail.com');
defined('ADMIN_EMAIL2') || define('ADMIN_EMAIL2', '89265965350@mail.ru');

defined('SEND_SMS_PROVIDER') || define('SEND_SMS_PROVIDER', 'SMS.RU');
defined('SEND_SMS_CONFIRM_REGISTRATION') || define('SEND_SMS_CONFIRM_REGISTRATION', true);
defined('SEND_EMAIL_CONFIRM_REGISTRATION') || define('SEND_EMAIL_CONFIRM_REGISTRATION', false);
defined('PASS_SALT') || define('PASS_SALT', '*****');

defined('IMG_UPLOAD_DIR') || define('IMG_UPLOAD_DIR', __DIR__ . '/../img/photo/');
defined('IMG_UPLOAD_ORDER_DIR') || define('IMG_UPLOAD_ORDER_DIR', __DIR__ . '/../img/order_photo/');
defined('IMG_SMALL_UPLOAD_DIR') || define('IMG_SMALL_UPLOAD_DIR', __DIR__ . '/../img/small_photo/');
defined('IMG_SMALL_WIDTH') || define('IMG_SMALL_WIDTH', 100); //ширина маленькой картинки
defined('IMG_BIG_WIDTH') || define('IMG_BIG_WIDTH', 450); //ширина большой картинки
defined('OLD_IMG_UPLOAD_DIR') || define('OLD_IMG_UPLOAD_DIR', __DIR__ . '/../img/old_photo/');
defined('IMG_PORTFOLIO_UPLOAD_DIR') || define('IMG_PORTFOLIO_UPLOAD_DIR', __DIR__ . '/../img/portfolio/');
defined('OLD_IMG_PORTFOLIO_UPLOAD_DIR') || define('OLD_IMG_PORTFOLIO_UPLOAD_DIR', __DIR__ . '/../img/old_portfolio/');

defined('IMG_DIR') || define('IMG_DIR', '/img/photo/');
defined('IMG_ORDER_DIR') || define('IMG_ORDER_DIR', '/img/order_photo/');
defined('IMG_SMALL_DIR') || define('IMG_SMALL_DIR', '/img/small_photo/');
defined('IMG_PORTFOLIO_DIR') || define('IMG_PORTFOLIO_DIR', '/img/portfolio/');
defined('LIMIT_UPLOAD_FILE_SIZE') || define('LIMIT_UPLOAD_FILE_SIZE', 7); //Mb

//временно - при запуске нужно включить кеширование!!!
defined('CACHE_TWIG') || define('CACHE_TWIG', false);
defined('TWIG_CACHE_DIR') || define('TWIG_CACHE_DIR', __DIR__ . '/../runtime/twig/');

defined('SMTP_SERVER_ADDR') || define('SMTP_SERVER_ADDR', '');
defined('SMTP_SERVER_PORT') || define('SMTP_SERVER_PORT', 25);
defined('SMTP_ENCRYPTION') || define('SMTP_ENCRYPTION', 'tls');
defined('MAIL_LOGIN') || define('MAIL_LOGIN', '---');
defined('MAIL_PASSWORD') || define('MAIL_PASSWORD', '---');

defined('MAIL_SUBJECT_BT') || define('MAIL_SUBJECT_BT', 'gemotest');
defined('MAIL_FROM_NAME_DEFAULT') || define('MAIL_FROM_NAME_DEFAULT', '');
defined('MAIL_FROM_EMAIL_DEFAULT') || define('MAIL_FROM_EMAIL_DEFAULT', 'info@bt.ru');

defined('SMS_RU_APP_ID') || define('SMS_RU_APP_ID', '****');
defined('SMS_SENDER_NAME') || define('SMS_SENDER_NAME', 'beautytime');

defined('WEB_SOCKET_CLIENT_HOST') || define('WEB_SOCKET_CLIENT_HOST', '185.224.138.112:8000');
defined('WEB_SOCKET_SERVER_HOST') || define('WEB_SOCKET_SERVER_HOST', '185.224.138.112:8000');

defined('PUSHER_AUTH_KEY') || define('PUSHER_AUTH_KEY', '16da265ff4ddb210c5a3');
defined('PUSHER_APP_ID') || define('PUSHER_APP_ID', '699783');
defined('PUSHER_SECRET_KEY') || define('PUSHER_SECRET_KEY', '372d8c02eff1db1c6cb7');

defined('YANDEX_GEOCODER_KEY') || define('YANDEX_GEOCODER_KEY', '2e238f09-8f19-4771-a412-8324c0c3adb0');
defined('YANDEX_GEOCODER_ZOOM') || define('YANDEX_GEOCODER_ZOOM', 10);
defined('YANDEX_GEOCODER_ADDRESS_ZOOM') || define('YANDEX_GEOCODER_ADDRESS_ZOOM', 15);
defined('YANDEX_GEOCODER_COORD') || define('YANDEX_GEOCODER_COORD', '55.76, 37.64');

defined('YOUTUBE_EMBED_PATH') || define('YOUTUBE_EMBED_PATH', 'https://www.youtube.com/embed/');
defined('ABOUT_COMPANY_YOUTUBE_URL') || define('ABOUT_COMPANY_YOUTUBE_URL', 'https://www.youtube.com/embed/xw5vSTtCLms');

defined('BANK_API_TOKEN') || define('BANK_API_TOKEN', 'YRF3C5RFICWISEWFR6GJ');//sberbank
defined('ROBO_LOGIN') || define('ROBO_LOGIN', 'beautytime24');
defined('ROBO_PASS_1') || define('ROBO_PASS_1', '******');
defined('ROBO_PASS_2') || define('ROBO_PASS_2', '*****');
defined('ROBO_PASS_TEST_1') || define('ROBO_PASS_TEST_1', 'a6T84La1KQb1QxFDWcDF');
defined('ROBO_PASS_TEST_2') || define('ROBO_PASS_TEST_2', 'Oj93DOFbq2ZyONGuH67L');
defined('ROBO_TEST') || define('ROBO_TEST', 0);
defined('ROBO_DEFAULT_SUM') || define('ROBO_DEFAULT_SUM', 100);
defined('INVOICE_DELIMITER') || define('INVOICE_DELIMITER', '');

defined('SEARCH_CHAR_COUNT') || define('SEARCH_CHAR_COUNT', 3);
defined('IMG_PATH_NO_AVATAR') || define('IMG_PATH_NO_AVATAR', '/img/master_no_foto.png');
defined('STATISTIC_DAYS') || define('STATISTIC_DAYS', 30);
defined('MINUTES_FOR_ACTIVE_USER') || define('MINUTES_FOR_ACTIVE_USER', 3);
defined('MASTER_RADIUS_FROM_CLIENT') || define('MASTER_RADIUS_FROM_CLIENT', 5000); //в метрах
defined('SALON_RADIUS_FROM_CLIENT') || define('SALON_RADIUS_FROM_CLIENT', 5000); //в метрах

defined('SEARCH_RAND_COUNT') || define('SEARCH_RAND_COUNT', 5); //количество рандомных объектов по умолчанию
defined('VIEW_IN_PROFILE') || define('VIEW_IN_PROFILE', 4); //количество выводимых объектов при просмотре профиля

defined('LOAD_SUMMARY_STATISTIC') || define('LOAD_SUMMARY_STATISTIC', false); //выводить ли статистику по мастерам
defined('SHOW_ORDERS_IN_MENU') || define('SHOW_ORDERS_IN_MENU', false); //отображать ли в меню список заказов

defined('PAGE_RESRESH_TIME') || define('PAGE_RESRESH_TIME', 60000); //время обновления страницы с заказами (в миллисекундах)

defined('IS_MODERATE_REVIEWS') || define('IS_MODERATE_REVIEWS', false);
defined('IS_MODERATE_MESSAGES') || define('IS_MODERATE_MESSAGES', false);
defined('REVIEWS_PROFILE_COUNT') || define('REVIEWS_PROFILE_COUNT', 20);

defined('STRING_STRIM_WIDTH') || define('STRING_STRIM_WIDTH', 40);
defined('API_MOS_KEY') || define('API_MOS_KEY', '18c1b8dcda066cac236d2a23151187ce');

defined('DAYS_FOR_NEW_USER') || define('DAYS_FOR_NEW_USER', 7); //как считать пользователя новым
