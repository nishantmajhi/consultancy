# Job Consultancy Helper

A comprehensive web app built with vanilla PHP, JavaScript, HTML, and CSS that helps to manage clients, jobs, applications, and communications in a consultancy business environment.

## Installation

### Option 1: Local PHP Server

1. Clone the repository

   ```sh
   git clone https://github.com/nishantmajhi/consultancy.git
   ```

2. Edit the .ENV file in the root directory

   ```ENV
   COMPANY_NAME=your_company_name
   DOMAIN_NAME=your_domain_name
   ```

3. Start the PHP server

   ```sh
   php -S localhost:1016
   ```

   You can use any available port number (e.g. 1016, 4000, 5000, etc.)

### Option 2: Docker

1. Clone the repository

   ```sh
   git clone https://github.com/nishantmajhi/consultancy.git
   ```

1. Edit the .ENV file in the root directory

   ```ENV
   COMPANY_NAME=your_company_name
   DOMAIN_NAME=your_domain_name
   ```

1. Build and Run the Docker image

   ```sh
   docker-compose up --detach
   ```

1. Access the application at <http://localhost:1016>

#### Stop the container

```sh
docker-compose stop
```
