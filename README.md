# Goals
- [ ] Make all game modes playable and beatable
- [ ] Implement MySQL-based saves

# Non-goals
* Implement anti-cheat

# How to host my own PTD2 server?
Requirements:
* nginx
* php-fpm

download the [server code](https://github.com/KGMats/PTD2-Server-Code/archive/refs/heads/master.zip) and unzip it


open your nginx.conf, it is located on "/etc/nginx/" if you are using linux and "C:/Program Files/nginx/conf" if you are using windows, and change the server root to: 

```
/{absolute path to PTD2-Server-Code}/public
```

Uncommit all lines between the

```
location ~ \.php$ {
```

and "}"

set fastcgi_param to

```
SCRIPT_FILENAME  $document_root$fastcgi_script_name
```

now just run nginx and php-fpm, and you will have a working PTD2 server on your computer!
