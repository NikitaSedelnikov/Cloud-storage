
## Подготовка инструментов для локального запуска приложения

- Установите среду разработки PhpStorm или Visual Studio Code, если они ещё не
установлены на вашем компьютере. Можете использовать любой редактор кода,
но в этих двух самый богатый функционал. 
- Установите XAMPP с официального сайта. В phpmyadmin создайте пустую базу
   данных cloud_task
- Рекомендуем использовать версию PHP не ниже 7.4
- Установите приложение **Postman**
- Так же, рекомендуется использовать консоль **GitBash**
- Будет неплохо иметь на борту **Composer**

## Установка приложения

- Находясь в консоли GitBash, перейдите в необходимую директорию командой: **cd <локальный адрес начиная с метки локального диска>**
- Необходимая директория находится в корневой папке **xampp --> htdocs** 
- Если у вас есть доступ - клоинруйте данные на ваш локальный репозиторий командой:
- **git clone https://github.com/NikitaSedelnikov/portfolio.git**

## Установка базы данных

- Находясь в БД **cloud_task** импортируйте файл базы данных MySQL в папке **1.MYSQLDB** в клонированном репозитории

## Настройка виртуального сервера

Перейдите в конфигурационный файл по адресу:
 <директория установки XAMPP>\apache\conf\extra\httpd-vhosts.conf
- В итоге, ваш конфигурационный файл должен выглядеть примерно так:
  
<VirtualHost *:80>\
ServerAdmin webmaster@dummy-host.example.com\
DocumentRoot "C:\xampp\htdocs\portfolio\cloud-task"\
ServerName cloud-storage.local\
ServerAlias www.cloud-storage.local\
ErrorLog "logs/dummy-host.example.com-error.log"\
CustomLog "logs/dummy-host.example.com-access.log" common\
<Directory C:\xampp\htdocs\portfolio\cloud-task>\
Options +Indexes +Includes +FollowSymLinks +MultiViews\
AllowOverride All\
Require all granted\
\<IfModule mod_rewrite.c>\
Options -MultiViews\
RewriteEngine On\
RewriteCond %{REQUEST_FILENAME} !-f\
RewriteRule ^(.*)$ /index.php [QSA,L]\
\</IfModule> (Без первого слэша)\
\</Directory> (Без первого слэша)\
\</VirtualHost> (Без первого слэша)

**Внимание! DocumentRoot и Directory должны выходить в директорию с файлом index.php**


- В файле xampp\apache\conf\httpd.conf убедитесь, что у строки: `Include conf/extra/httpd-vhosts.conf` отсутствует знак **"#"** в начале


## Запуск виртуального сервера

- Перейдите в приложение Postman и импортируйте приложенный файл в папке **1.Postman-json**
- Все приложенные в файле адреса с готовыми данными для проверки работоспособности. Адреса можно выполнять по порядку.

## Возможности приложения

- Регистрация, аутентификация, восстановление пароля *(add user, login-in-user, logout, reset-password, password-update)
- Просмотр списка загруженных вами файлов (file-list)
- Просмотр доступной для вас информации о пользователях/конкретном пользователе (user-list, get-user)
- Обновление своих собственных данных (update-user)
- Загрузка файлов весом до 2ГБ (add-file)
- Получение информации о конкретном файле, если у вас есть на него права (file-get)
- Переименовать/удалить файл (rename-file, remove-file)
- Создание папок и перетаскивания в них необходимых файлов (add-dir, set-file-in-dir)
- Переименовать папку (rename-dir)
- Поделиться файлом с пользователем или лишить его прав (share-file, get-into-file, delete-user-share)
## Возможности администратора
- Просмотр полной информации о пользователях или конкретном пользователе (admin-list, admin-get)
- Изменение информации о пользователе (update-user-admin)
- Удалить пользователя (delete-user)

*Название доступных адресов в Postman


## Используемые зависимости

- PHPMailer
