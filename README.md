[![](https://img.shields.io/badge/razor_1.0.0-passing-dark_green)](https://github.com/gongahkia/razor/releases/tag/1.0.0) 
[![](https://img.shields.io/badge/razor_2.0.0-passing-green)](https://github.com/gongahkia/razor/releases/tag/1.0.0) 

# `Razor` 🏐

Simple Full Stack Password Manager Web App.

Made to practise [the stack(s)](#stack) *(and migrating between them)* for my internship. 

## Stack

[`Razor` V1.0.0](#razor-v100)

* [**Frontend**](./razor-app-v1/): Vue.js, Netlify
* [**Backend**](./src/): PHP, AWS EC2
* [**Database**](./src/): SQL, PostgreSQL

[`Razor` V2.0.0](#razor-v200)

* [**Frontend**](./razor-app-v2/): Vue.js, Netlify
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

![](./asset/reference/architecture-1.png)

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

![](./asset/reference/architecture-2.png)

#### DB

```mermaid
erDiagram
    USERS ||--o{ PASSWORDS : stores
    USERS {
        string uid PK "Firebase Auth UID"
        string email "User's email"
        timestamp createdAt "Account creation time"
        timestamp lastLogin "Last login time"
    }
    PASSWORDS {
        string id PK "Auto-generated key"
        string user_id FK "References USERS.uid"
        string website "Website URL"
        string username "Account username"
        string encrypted_password "AES encrypted password"
        timestamp createdAt "Password creation time"
        timestamp updatedAt "Last update time"
    }
```

## Reference

The name `Razor` is in reference to [Razor](https://hunterxhunter.fandom.com/wiki/Razor) (レイザー), a prominent [Game Master](https://hunterxhunter.fandom.com/wiki/G.I._Game_Masters) from [Greed Island](https://hunterxhunter.fandom.com/wiki/Greed_Island). He emerges as a minor antagonist in the [Greed Island arc](https://hunterxhunter.fandom.com/wiki/Greed_Island_arc) of the ongoing manga series, [HunterXhunter](https://hunterxhunter.fandom.com/wiki/Hunterpedia). `Razor` is also a reference to [Occam's razor](https://en.wikipedia.org/wiki/Occam%27s_razor).

![](./asset/logo/razor.webp)