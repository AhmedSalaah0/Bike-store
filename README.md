# Bike-Store API

Bike-Store API Using PHP and Mysql

## API Reference

#### Register

```http
  Post /auth/register
```

| Parameter   | Type     | Description                    |
| :--------   | :------- | :-------------------------     |
| `first_name`| `string` | **Required**. user first_name  |
| `last_name` | `string` | **Required**. user last_name   |
| `email`     | `string` | **Required**. Registered email |
| `password`  | `string` | **Required**. email password   |
| `phone_number`  | `string` | **Required**. user phone_number |

#### login

```http
  Post /auth/login
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `email`     | `string` | **Required**. Registered email |
| `password`  | `string` | **Required**. email password   |


