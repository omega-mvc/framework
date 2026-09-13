<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\InnerQuery;
use Omega\Database\Query\Join\InnerJoin;
use Omega\Database\Query\Select;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers('Omega\Database\Query\Query');
covers(InnerQuery::class);
covers(InnerJoin::class);
covers(Select::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can select sub query using where', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'AUTO_INCREMENT columns');

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );')->execute();
    $this->pdo->query('CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        total_amount DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );')->execute();
    $this->pdo->query('INSERT INTO users (name, email) VALUES
        ("Alice", "alice@example.com"),
        ("Bob", "bob@example.com"),
        ("Charlie", "charlie@example.com")
    ;')->execute();
    $this->pdo->query('INSERT INTO orders (user_id, total_amount) VALUES
        (1, 1200),
        (2, 800),
        (3, 1500)
    ;')->execute();

    $users = new Select('users', ['name', 'email'], $this->pdo);
    $users->whereIn('id', (new Select('orders', ['user_id'], $this->pdo))
        ->compare('total_amount', '>', 1000));
    $users = $users->all() ?: [];

    
    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('Alice');
    expect($users[1]['name'])->toBe('Charlie');
})->with(ManagesDatabase::engineProvider());

test('it can select sub query using from', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'AUTO_INCREMENT columns');

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );')->execute();
    $this->pdo->query('CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        total_amount DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );')->execute();
    $this->pdo->query('CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255)
    );')->execute();
    $this->pdo->query('CREATE TABLE sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        quantity INT,
        price DECIMAL(10, 2)
    );')->execute();
    $this->pdo->query('INSERT INTO users (name, email) VALUES
        ("Alice", "alice@example.com"),
        ("Bob", "bob@example.com"),
        ("Charlie", "charlie@example.com")
    ;')->execute();
    $this->pdo->query('INSERT INTO orders (user_id, total_amount) VALUES
        (1, 1200),
        (2, 800),
        (3, 1500)
    ;')->execute();
    $this->pdo->query('INSERT INTO products (name) VALUES
        (\'Laptop\'), (\'Phone\'), (\'Tablet\')
    ;')->execute();
    $this->pdo->query('INSERT INTO sales (product_id, quantity, price) VALUES
        (1, 2, 1000),  -- Laptop
        (2, 3, 800),   -- Phone
        (3, 1, 600);   -- Tablet
    ')->execute();

    $products = new Select(
        new InnerQuery(
            (new Select(
                'sales',
                ['product_id', 'SUM(quantity) AS total_quantity', 'SUM(quantity * price) AS total_sales'],
                $this->pdo
            ))->groupBy('product_id'),
            'sub'
        ),
        ['sub.product_id', 'sub.total_quantity', 'sub.total_sales'],
        $this->pdo
    );

    $products = $products->get();

    expect($products)->toHaveCount(3);
})->with(ManagesDatabase::engineProvider());

test('it can select sub query using join', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'AUTO_INCREMENT columns');

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        city VARCHAR(255)
    );')->execute();
    $this->pdo->query('CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        amount DECIMAL(10, 2),
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );')->execute();
    $this->pdo->query('INSERT INTO customers (name, city) VALUES
        ("Alice", "New York"),
        ("Bob", "Los Angeles"),
        ("Charlie", "Chicago")
    ;')->execute();
    $this->pdo->query('INSERT INTO transactions (customer_id, amount, transaction_date) VALUES
        (1, 600, "2024-12-01 10:00:00"),  -- Alice
        (1, 500, "2024-12-02 12:00:00"),  -- Alice
        (2, 400, "2024-12-01 11:00:00"),  -- Bob
        (3, 800, "2024-12-03 14:00:00");  -- Charlie
    ;')->execute();

    $customers = new Select(
        'customers',
        ['customers.name', 'sub.total_spent'],
        $this->pdo
    );

    $customers->join(
        InnerJoin::ref(
            new InnerQuery(
                (new Select(
                    'transactions',
                    ['customer_id', 'SUM(amount) AS total_spent'],
                    $this->pdo
                ))
                ->groupBy('customer_id'),
                'sub'
            ),
            'id',
            'customer_id'
        )
    );

    $customers = $customers->get();

    expect($customers)->toHaveCount(3);
})->with(ManagesDatabase::engineProvider());