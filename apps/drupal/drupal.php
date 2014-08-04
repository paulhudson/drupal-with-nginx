<?php
if (isset($VAR->domain->physicalHosting->fpmSocket)) {
    $fpmsocket = $VAR->domain->physicalHosting->fpmSocket;
} else {
    if ($OPT['ssl']) {
        $proxy_pass = 'https://'. $OPT['ipAddress']->proxyEscapedAddress . ':' . $OPT['backendPort'];
    } else {
        $proxy_pass = 'http://'. $OPT['ipAddress']->proxyEscapedAddress .':'. $OPT['backendPort'];
    }
}
?>

location ~ / {

    location ^~ /system/files/ {
    <?php if (isset($VAR->domain->physicalHosting->fpmSocket)) : ?>
        include apps/drupal/fastcgi_drupal.conf;
        fastcgi_pass <?php echo $fpmsocket; ?>;
    <?php else : ?>
        #proxy_pass http://phpapache/index.php?q=$uri;
        proxy_pass <?php echo $proxy_pass; ?>/index.php?q=$uri;
        proxy_set_header Connection '';
    <?php endif; ?>
        log_not_found off;
    }

    location ^~ /sites/default/files/private/ {
        internal;
    }

    location ^~ /system/files_force/ {
      <?php if (isset($VAR->domain->physicalHosting->fpmSocket)) : ?>
        include apps/drupal/fastcgi_drupal.conf;
        fastcgi_pass <?php echo $fpmsocket; ?>;
      <?php else : ?>
        #proxy_pass http://phpapache/index.php?q=$uri;
        proxy_pass <?php echo $proxy_pass; ?>/index.php?q=$uri;
        proxy_set_header Connection '';
      <?php endif; ?>
        log_not_found off;
    }

    location ~* /imagecache/ {
        #include apps/drupal/hotlinking_protection.conf;

        access_log off;
        expires 30d;
        try_files $uri @drupal;
    }

    location ~* /files/styles/ {
        #include apps/drupal/hotlinking_protection.conf;

        access_log off;
        expires 30d;
        try_files $uri @drupal;
    }

    location ^~ /sites/default/files/advagg_css/ {
        expires max;
        add_header ETag '';
        add_header Last-Modified 'Wed, 20 Jan 1988 04:20:42 GMT';
        add_header Accept-Ranges '';

        location ~* /sites/default/files/advagg_css/css[_[:alnum:]]+\.css$ {
            access_log off;
            try_files $uri @drupal;
        }
    }

    location ^~ /sites/default/files/advagg_js/ {
        expires max;
        add_header ETag '';
        add_header Last-Modified 'Wed, 20 Jan 1988 04:20:42 GMT';
        add_header Accept-Ranges '';

        location ~* /sites/default/files/advagg_js/js[_[:alnum:]]+\.js$ {
            access_log off;
            try_files $uri @drupal;
        }
    }

    location ~* ^.+\.(?:css|cur|js|jpe?g|gif|htc|ico|png|html|xml|otf|ttf|eot|woff|svg)$ {

        access_log off;
        expires 30d;
        tcp_nodelay off;
        ## Set the OS file cache.
        open_file_cache max=3000 inactive=120s;
        open_file_cache_valid 45s;
        open_file_cache_min_uses 2;
        open_file_cache_errors off;
    }

    location ~* ^.+\.(?:pdf|pptx?)$ {
        expires 30d;
        tcp_nodelay off;
    }

    location ^~ /sites/default/files/audio/mp3 {
        location ~* ^/sites/default/files/audio/mp3/.*\.mp3$ {
            directio 4k; # for XFS
            #directio 512; # for ext3 or similar (block alignments)
            tcp_nopush off;
            aio on;
            output_buffers 1 2M;
        }
    }

    location ^~ /sites/default/files/audio/ogg {
        location ~* ^/sites/default/files/audio/ogg/.*\.ogg$ {
            directio 4k; # for XFS
            #directio 512; # for ext3 or similar (block alignments)
            tcp_nopush off;
            aio on;
            output_buffers 1 2M;
        }
    }

    location ^~ /help/ {
        location ~* ^/help/[^/]*/README\.txt$ {
          <?php if (isset($VAR->domain->physicalHosting->fpmSocket)) : ?>
            include apps/drupal/fastcgi_drupal.conf;
            fastcgi_pass <?php echo $fpmsocket; ?>;
          <?php else : ?>
            #proxy_pass http://phpapache/index.php?q=$uri;
            proxy_pass <?php echo $proxy_pass; ?>/index.php?q=$uri;
            proxy_set_header Connection '';
          <?php endif; ?>
        }
    }

    location ~* ^(?:.+\.(?:htaccess|make|txt|engine|inc|info|install|module|profile|po|pot|sh|.*sql|test|theme|tpl(?:\.php)?|xtmpl)|code-style\.pl|/Entries.*|/Repository|/Root|/Tag|/Template)$ {
        return 404;
    }

    try_files $uri @drupal;
}

########### Security measures ##########

#include apps/drupal/admin_basic_auth.conf;

location @drupal {
  <?php if (isset($VAR->domain->physicalHosting->fpmSocket)) : ?>
    include apps/drupal/fastcgi_drupal.conf;
    fastcgi_pass <?php echo $fpmsocket; ?>;

    include apps/drupal/microcache_fcgi.conf;
    #include apps/drupal/microcache_fcgi_auth.conf;
  <?php else : ?>
    #proxy_pass http://phpapache/index.php?q=$uri;
    proxy_pass <?php echo $proxy_pass; ?>/index.php?q=$uri;
    proxy_set_header Connection '';

    include apps/drupal/microcache_proxy.conf;
    #include apps/drupal/microcache_proxy_auth.conf;
  <?php endif; ?>
    #track_uploads uploads 60s;
}

location @drupal-no-args {
  <?php if (isset($VAR->domain->physicalHosting->fpmSocket)) : ?>
    include apps/drupal/fastcgi_no_args_drupal.conf;
    fastcgi_pass <?php echo $fpmsocket; ?>;

    include apps/drupal/microcache_fcgi.conf;
    #include apps/drupal/microcache_fcgi_auth.conf;
  <?php else : ?>
    #proxy_pass http://phpapache/index.php?q=$uri;
    proxy_pass <?php echo $proxy_pass; ?>/index.php?q=$uri;
    proxy_set_header Connection '';

    include apps/drupal/microcache_proxy.conf;
    #include apps/drupal/microcache_proxy_auth.conf;
  <?php endif; ?>
}

location ^~ /.bzr {
    return 404;
}

location ^~ /.git {
    return 404;
}

location ^~ /.hg {
    return 404;
}

location ^~ /.svn {
    return 404;
}

location ^~ /.cvs {
    return 404;
}

location ^~ /patches {
    return 404;
}

location ^~ /backup {
    return 404;
}

location = /robots.txt {
    access_log off;
    ## Add support for the robotstxt module
    ## http://drupal.org/project/robotstxt.
    try_files $uri @drupal-no-args;
}

location = /rss.xml {
    try_files $uri @drupal-no-args;
}

location = /sitemap.xml {
    try_files $uri @drupal-no-args;
}

location = /favicon.ico {
    expires 30d;
    try_files /favicon.ico @empty;
}

location @empty {
    expires 30d;
    empty_gif;
}

location ~* ^.+\.php$ {
    return 404;
}
