# Платежный модуль CrystalPAY.io для billmanager
Позволяет подключить приём платежей в панель billmanager.

![image](https://github.com/CrystalPAY-io/billmanager-module/assets/124487204/91716020-53a6-47d2-a689-e3b188e57f81)


## Требования для работы
- Установленный `php`
- Установленные дополнения `php-xml`, `php-curl`, `php-mysql`, `php-common`

Пример установки всех пакетов для Ubuntu 20.04
```
apt-get update -y
apt install php php-xml php-curl php-mysql php-common -y
```

## Установка модуля
1. Скачайте [последнюю версию модуля](https://github.com/CrystalPAY-io/billmanager-module/releases)
2. Откройте скачанный архив, перейдите в папку `billmanager-module-ВЕРСИЯ`
3. Распакуйте содержимое папки из архива по пути на сервере `/usr/local/mgr5`
4. Выполните команду `chmod 755 /usr/local/mgr5/cgi/crystalpay* ; chmod 755 /usr/local/mgr5/paymethods/pmcrystalpay.php` в консоли сервера, для выставления прав доступа на скрипты
5. Выполните команду `chmod 755 /usr/local/mgr5/paymethods/pmcrystalpay.php` в консоли сервера
6. Выполните команду `killall core` в консоли сервера, для перезапуска панели
7. Перейдите в раздел настройки методов оплаты, создайте новый метод оплаты - `CrystalPAY`

![image](https://github.com/CrystalPAY-io/billmanager-module/assets/124487204/70ea9f53-6ccd-450c-8550-2a72bf26c0f5)

8. Заполните поля конфигурации, модуль готов к работе!

&nbsp;
> При возникновении ошибок связанных с модулем, проверьте корректность установки требуемых пакетов и перезагрузите сервер командой `reboot`, затем создайте новый метод оплаты.
