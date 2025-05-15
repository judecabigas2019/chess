FROM php:8.1-cli

# Copy files
COPY . /app
WORKDIR /app

# Install basic dependencies
RUN apt-get update && apt-get install -y \
    procps \
    && chmod +x /app/stockfish

# Start PHP dev server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
