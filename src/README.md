
## Generate Swoole.docset


### fetch markdown && generate html
``` 
> php ./run.php
```

### import database 

``` 
> tar xzvf Swoole.tgz 
> cd /path/to/project/Swoole.docset/Contents/Resources
> sqlite3 docSet.dsidx
> .read docset.sql

```

### copy html to document
```
> cp -R /path/to/project/src/html/*  Swoole.docset/Contents/Resources/Documents  
```
