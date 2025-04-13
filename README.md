[![](https://img.shields.io/badge/razor_1.0.0-passing-green)](https://github.com/gongahkia/razor/releases/tag/1.0.0) 

# `Razor` 🏐

Simple Full Stack Password Manager Web App.

Made to practise [the stack](#stack) for my internship. 

## Stack

`Razor` V1.0.0

* [**Frontend**](./razor-app/): Vue.js, Netlify
* [**Backend**](./src/): PHP, AWS EC2
* [**Database**](./src/): SQL, PostgreSQL

`Razor` V2.0.0

* [**Frontend**](./razor-app/): Vue.js, Netlify
* [**Backend**](./src/): Node.js, DigitalOcean Droplets
* [**Database**](./src/): Firebase Realtime Database

## Usage


```console
$ git clone https://github.com/gongahkia/razor
$ sudo service postgresql start
$ psql -U postgres -c "CREATE DATABASE razordb;"
$ psql -U postgres -d razor -f src/backend/db/schema.sql
$ cd/razor-app
$ npm run serve
```

## Architecture

### `Razor` V1.0.0

#### Overview

![](./asset/reference/architecture.png)

#### DB

```mermaid
erDiagram
    USERS {
        int id PK "Auto Increment"
        varchar(255) username "NOT NULL, UNIQUE"
        varchar(255) password_hash "NOT NULL"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
    }
    
    PASSWORDS {
        int id PK "Auto Increment"
        int user_id FK "NOT NULL"
        varchar(255) website "NOT NULL"
        varchar(255) username "NOT NULL"
        varchar(255) encrypted_password "NOT NULL"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
    }
    
    USERS ||--o{ PASSWORDS : "stores"
```

### `Razor` V2.0.0

#### Overview

#### DB

```mermaid
...
```

## Reference

The name `Razor` is in reference to [Razor](https://hunterxhunter.fandom.com/wiki/Razor) (レイザー), a prominent [Game Master](https://hunterxhunter.fandom.com/wiki/G.I._Game_Masters) from [Greed Island](https://hunterxhunter.fandom.com/wiki/Greed_Island). He emerges as a minor antagonist in the [Greed Island arc](https://hunterxhunter.fandom.com/wiki/Greed_Island_arc) of the ongoing manga series, [HunterXhunter](https://hunterxhunter.fandom.com/wiki/Hunterpedia). `Razor` is also a reference to [Occam's razor](https://en.wikipedia.org/wiki/Occam%27s_razor).

![](./asset/logo/razor.webp)