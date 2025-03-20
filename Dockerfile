# Use an official PHP image from Docker Hub
FROM php:8.1-apache

# Copy your PHP files to the appropriate directory in the container
COPY . /var/www/html/

# Expose port 80 to access the application
EXPOSE 80
