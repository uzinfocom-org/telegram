# Nginx error reporter

🤖 | Server holatlarini minimal ogohlantirib turish uchun teleram bot.


`config.php` fayli orqali kerakli konfiguratsiyalar kiritilgach `crontab -e` buyrug'i orqali quyidagi komanda qo'yiladi.

```
@reboot /usr/bin/php /home/crone/server_crone.php > /dev/null 2>&1
```