<?php return[
    'default' => 'mysql',
    'connections' => [
        //Default configuration
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        /**
        * Another DB connection config
        */
        'mysql2' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_SECOND', 'localhost'),
            'port' => env('DB_PORT_SECOND', 3306),
            'database' => env('DB_DATABASE_SECOND', 'forge'),
            'username' => env('DB_USERNAME_SECOND', 'forge'),
            'password' => env('DB_PASSWORD_SECOND', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],
        'virginiamongodb' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/VirginiaIPTV?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'VirginiaIPTV'
        ],
        'iboappatlas' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/IBOIPTV?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOIPTV'
        ],
        'abeplayertv' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/AbePlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'AbePlayer'
        ],
        'bobPlayer' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/BobPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'BobPlayer'
        ],
        'macplayeratlas' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/MacPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'MacPlayer'
        ],
        'ktnPlayerAtlas' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/KtnPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'KtnPlayer'
        ],
        'AllPlayerAtlas' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/AllPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'AllPlayer'
        ],
        'HushPlayAtlas' => [
            'driver' => 'mongodb',
//            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/HushPlayer?authSource=admin&retryWrites=true&w=majority'),
            'dsn' => env('DB_URI', 'MONGO_DB_SERVER=mongodb://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@185.183.33.67:24365/HushPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'HushPlayer'
        ],
        'FamilyPlayerAtlas' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/FamilyPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'FamilyPlayer'
        ],
        'IBOSSPlayer' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/IBOSSPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOSSPlayer'
        ],
        'King4kPlayer' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/King4kPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'King4kPlayer'
        ],
        'IBOXXPlayer' => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://iboiptv:adlWH2nRZEGK9a8OA9Bm7YGKJ8dkRDdh@cluster0.0mb65.mongodb.net/IBOXXPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOXXPlayer'
        ],
        'bobProTvAtlas'=> [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/BobProTV?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'BobProTV'
        ],
        'iboProTv'=> [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/IBOProTV?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOProTV'
        ],
        'IBOStb'=> [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/IBOStb?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOStb'
        ],
        "IBOSOL" => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/IBOSOLPlayer?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'IBOSOLPlayer'
        ],
        "ibocdnPanel" => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/ibocdn_panel?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'ibocdn_panel'
        ],
        "duplexPlayer" => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/DuplexTV?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'DuplexTV'
        ],
        "flixNetPlayer" => [
            'driver' => 'mongodb',
            'dsn' => env('DB_URI', 'mongodb+srv://zoneibosss:SCbMuKL0k08SWp1K@cluster0.mrw6b.mongodb.net/FlixNet?authSource=admin&retryWrites=true&w=majority'),
            'database' =>'FlixNet'
        ]
    ]
];
