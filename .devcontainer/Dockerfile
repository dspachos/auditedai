FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libpq-dev \
    libzip-dev \
    libxml2-dev \
    sqlite3 \
    libsqlite3-dev \
    zsh \
    curl \
    wget \
    gnupg \
    nodejs \
    npm \
    sudo \
    lsb-release \
    openssh-client

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Drush
RUN composer global require drush/drush

# Install Oh My Zsh
RUN sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" "" --unattended

# Install zsh-autosuggestions
RUN git clone https://github.com/zsh-users/zsh-autosuggestions ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/plugins/zsh-autosuggestions

# Install zsh-syntax-highlighting
RUN git clone https://github.com/zsh-users/zsh-syntax-highlighting.git ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/plugins/zsh-syntax-highlighting

# Update .zshrc to enable plugins and set ZSH as default shell
RUN sed -i 's/plugins=(git)/plugins=(git zsh-autosuggestions zsh-syntax-highlighting)/' ~/.zshrc && \
    echo 'export PATH="$PATH:./vendor/bin"' >> ~/.zshrc && \
    chsh -s $(which zsh)

# Set working directory
WORKDIR /var/www/html

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/web!g' /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN npm i -g yarn

# Expose port 80
EXPOSE 80

RUN apt-get autoremove -y
RUN rm -rf /var/lib/apt/lists/*

RUN git config --global --add safe.directory /workspaces/ecan

# Start Apache
CMD ["apache2-foreground"]
