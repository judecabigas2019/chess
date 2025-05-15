FROM php:8.1-cli

# Copy everything to /app
COPY . /app
WORKDIR /app

# Make the Stockfish binary executable
RUN chmod +x /app/stockfish

# Start PHP's built-in web server on port 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
