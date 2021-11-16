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


open a text editor with administrative permissions and open nginx.conf file, it is located on "/etc/nginx/" if you are using linux and "C:/Program Files/nginx/conf" if you are using windows, and change the server root to: 

```
/{absolute path to PTD2-Server-Code}/public
```

Uncomment php FastCGI lines

```
location ~ \.php$ {
	fastcgi_pass    127.0.0.1:9000;
	fastcgi_index   index.php;
	fastcgi_param   SCRIPT_FILENAME;
	include	  			fastcgi_params
	}
```

set fastcgi_param to

```
SCRIPT_FILENAME  $document_root$fastcgi_script_name
```

now just run nginx and php-fpm, and you will have a working PTD2 server on your computer!

# For developers:
The code is divided as follows:
* Things related to save are in json.php, for json saves (use only if your server is not public) and in MySQL.php for SQL saves.
* Server methods (save/load accounts etc.) are in ptd2_save_12.php
* Auxiliary functions such as obfuscation of data are in Utils.php
