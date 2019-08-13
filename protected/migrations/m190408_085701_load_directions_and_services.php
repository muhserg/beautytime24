<?php

class m190408_085701_load_directions_and_services extends CDbMigration
{
    public function safeUp()
    {
        $this->execute("
            DELETE FROM master_profile_direction;
            DELETE FROM master_profile_service;
            DELETE FROM salon_profile_direction;
            DELETE FROM salon_profile_service;
            
            DELETE FROM buf_order;
            DELETE FROM buf_master_order;
            DELETE FROM buf_salon_order;
            DELETE FROM orders;
            DELETE FROM service;
            DELETE FROM direction;


            ALTER TABLE direction AUTO_INCREMENT = 1;
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Парикмахерские услуги', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Ногтевой сервис', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Наращивание ресниц', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Эпиляция', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Брови', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Косметология', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Массаж', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Тату', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Похудение', now(), now());
            
            
            ALTER TABLE service AUTO_INCREMENT = 1;
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Стрижка женская', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Стрижка мужская', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Окрашивание волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Кератиновое выпрямление', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Ботокс для волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Нанопластика волос ', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Наращивание волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Свадебная причёска', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Укладка волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Ламинирование волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Химическая завивка', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Плетение кос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Дреды или афрокосички', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Лечение волос', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Маникюр', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Педикюр', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Наращивание ногтей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Наращивание ногтей (коррекция)', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Снятие покрытия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Дизайн ногтей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Парафинотерапия', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Наращивание ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Снятие наращенных ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Наращивание ресниц пучками', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Ламинирование ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Ботокс ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Завивка ресниц', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Шугаринг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Восковая эпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Лазерная Эпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Фотоэпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Электроэпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Элос Эпиляция', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Архитектура или форма  бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Коррекция бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Ламинирование бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Ботокс бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Окрашивание бровей', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Чистка лица', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Массаж лица', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Уход за лицом', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Пилинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Лифтинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Мезотерапия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Дарсонвализация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Биоревитализация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Инъекции', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Фотоомоложение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Пластика', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Удаление рубцов', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Удаление деффектов', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Коллаген', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Аппаратная косметология', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Прокол ушей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Моделирование', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Микротоки', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Липолитики', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Массаж', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Обёртывание', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Lpg', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Аппаратный массаж', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Татуаж', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Микроблейдинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Татуировка', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Перманентный макияж', now(), now());
            
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Коррекция фигуры', now(), now());
        ");
    }

    public function safeDown()
    {
        $this->execute("
            DELETE FROM master_profile_direction;
            DELETE FROM master_profile_service;
            DELETE FROM salon_profile_direction;
            DELETE FROM salon_profile_service;
            
            DELETE FROM buf_order;
            DELETE FROM buf_master_order;
            DELETE FROM buf_salon_order;
            DELETE FROM orders;
            DELETE FROM service;
            DELETE FROM direction;


            ALTER TABLE direction AUTO_INCREMENT = 1;
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Парикмахерские услуги', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Ногтевой сервис', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Наращивание ресниц', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Эпиляция', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Брови', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Косметология', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Татуаж', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Массаж', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Макияж', now(), now());
            INSERT INTO direction(name, created_at, updated_at) VALUES ('Загар', now(), now());
            
            
            ALTER TABLE service AUTO_INCREMENT = 1;
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Стрижки', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Укладка волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Уход за волосами', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Окрашивание волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Прически', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Завивка волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Лечение волос', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Декапирование/Карвинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Афрокосички/Дреды', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (1, 'Наращивание волос', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Маникюр женский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Маникюр мужской', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Покрытие гель лак', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Наращивание ногтей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Дизайн ногтей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Укрепление ногтей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Уход за руками', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Мужской педикюр', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (2, 'Женский педикюр', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Наращивание уголков', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Классический объём', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Двойной объём', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Тройной объём', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Голивудский объём', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Наращивание пучками', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Завивка ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Снятие наращенных ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Ламинирование ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Наращивание цветных ресниц', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (3, 'Восстановление ресниц', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Шугаринг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Восковая эпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Лазерная Эпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Эпиляция фитосмолой', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Фотоэпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Элос Эпиляция', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Эпиляция нитью', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (4, 'Электро Эпиляция', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Архитектура бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Форма бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Коррекция бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Окрашивание бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Наращивание бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Ламинирование бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Микроблейдинг бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (5, 'Татуаж бровей', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Фотоомоложение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Озонотерапия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Ионофорез', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Дарсонвализация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Пилинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Лечение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Мезотерапия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Маски для лица', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Чистка лица', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Криомассаж', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Микротоки', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Консультация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Ботокс', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Увеличение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Биоревитализация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Коррекция морщин', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Удаление новообразований', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Парафинотерапия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Аппаратная косметология', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Контурная пластика', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Кавитация', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Липолиз', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Лазерное омоложение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Лифтинг', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Элос - омоложение', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (6, 'Ринопластика', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Татуаж бровей', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Мушка', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Глаза', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Удаление', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (7, 'Пудровое напыление бровей', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Антицеллюлитный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Аппаратный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Аюрведический', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Балийский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Банный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Баночный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Вакумно - роликовый', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Восстановительный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Гавайский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Детский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Китайский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Классический', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Лечебный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Лимфодренажный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Для детей с ДЦП', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Для беременных', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'При остеохондрозе', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Медовый', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Спортивный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Скульптурный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Рефлексотерапия', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Стоун', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Тайский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Тибетский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Тонизирующий', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Точечный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Турецкий', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Холистический', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Японский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Испанский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Креольский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Египетский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Масляный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Шведский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Индийский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Бразильский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Японский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (8, 'Остеопатический', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Вечерний', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Возрастной', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Грим', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Детский', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Дневной', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Естественный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Клубный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Для фотосессии', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Свадебный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Мужской', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Подиумный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Фантазийный', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'На выпускной', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Демакияж', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Smoky eyes', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Экспресс', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (9, 'Контуринг', now(), now());
            
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (10, 'Солярий', now(), now());
            INSERT INTO service(direction_id, name, created_at, updated_at) VALUES (10, 'Моментальный загар', now(), now());
        ");
    }
}
