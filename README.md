# Генерация RSS-канала Yandex.Turbo для CMS Битрикс

### ALFA версия!

## Описание
Модуль Yandex Turbo позволяет гибко настроить RSS 2.0 выгрузку новостей для сервиса Яндекса Турбо‑страницы

## Установка
```shell script
composer require varrcan/yaturbo
```
Активация модуля в админке.

## Возможности
- Создание нескольких выгрузок с разными настройками
- Разбиения больших выгрузок на части
- Поддержка 1С-Битрикс Агентов
- Загрузка данных через API в Яндекс Вебмастер

## Технические требования:
- Минимальная версия PHP 7.1
- Минимальная версия модуля iblock 19.0.0
- Агенты Битрикс на cron

## TODO
- Обрамление свойств в заданные теги
- ~~Агенты~~
- Выборка данных через новую ORM
- Выгрузка в Яндекс, только если появились новые элементы
- ~~API~~
- Форма настроек на react
- Предпросмотр
- Отображение ошибок в канале
- Выгрузка больше 500 элементов


## Changelog
- 1.0.6 Bug fix, переработаны агенты, обработка ошибок API
- 1.0.5 Bug fix
- 1.0.4 Fix агентов экспорта файлов
- 1.0.3 Добавлена выгрузка файлов в Яндекс через API
- 1.0.0 Alfa
