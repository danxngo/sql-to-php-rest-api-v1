# REST API Documentation

This RESTful API is built using PHP and utilizes a MySQL database for data storage. It provides endpoints for user authentication and data manipulation.

## Setup

1. Clone the repository.
2. Run `composer install` to install the required dependencies.
3. Copy the `.env.example` file to `.env` and fill in your database and Firebase credentials.

## Endpoints

### User Authentication

- `POST /login`: Logs in a user. Expects a JSON body with `email` and `password` fields. Returns a JWT token if successful.

- `POST /signup`: Registers a new user. Expects a JSON body with `email`, `password`, and `name` fields. Returns a JWT token if successful.

- `POST /logout`: Logs out a user. Expects an `Authorization` header with a valid JWT token.

- `GET /validateToken`: Validates a JWT token. Expects an `Authorization` header with a valid JWT token.

### Data Manipulation

- `GET /user`: Retrieves user data. Requires a valid JWT token in the `Authorization` header.

- `POST /user`: Creates a new user. Requires a valid JWT token in the `Authorization` header and a JSON body with user data.

- `PUT /user`: Updates a user. Requires a valid JWT token in the `Authorization` header and a JSON body with the updated data.

- `DELETE /user`: Deletes a user. Requires a valid JWT token in the `Authorization` header.

## Error Handling

The API uses custom error and exception handlers to return JSON responses in case of errors. If an error occurs, the API will return a JSON object with an `error` field containing the error message.

## CORS

The API is configured to allow all origins and supports the following methods: GET, POST, PUT, DELETE, OPTIONS. It also allows the `Content-Type` and `Authorization` headers.

## Middleware

The API uses middleware for token verification. This middleware is applied to routes that require authentication. If the token is invalid or expired, the API will return a `401 Unauthorized` status code with an error message.

## Running the API

To start the API, simply run `php -S localhost:8000` in the root directory of the project. The API will be available at [http://localhost:8000](http://localhost:8000).

Please note that this is a basic documentation. Depending on the complexity of your API, you might want to provide more detailed information about each endpoint, including the expected request format, possible response codes and their meanings, and examples of requests and responses.
