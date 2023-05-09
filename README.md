# AppSkeleton PHP Framework

Welcome to *AppSkeleton*, a PHP framework designed to help you quickly create robust and scalable web applications.
This framework provides a set of tools and libraries to help you streamline your development process,
while adhering to best practices and industry standards.

## Features
* Lightweight and easy to install.
* Follows the Model-View-Controller (MVC) pattern for clear separation of concerns.
* Supports modular application architecture.
* Built-in routing system for handling URLs and request methods.
* Includes a robust database abstraction layer for working with different database engines.
* Supports multiple caching mechanisms.
* Provides a templating engine for easy separation of presentation and business logic.
* Comes with built-in security features, including CSRF protection and encryption.
* Includes a variety of helper functions and libraries for common tasks, such as form validation and file handling.

## Installation
To get started with *AppSkeleton*, simply clone the repository or download the latest release from GitHub.
Then, run composer install to install any dependencies.

```bash
git clone https://github.com/esurov/app-skeleton.git
cd app-skeleton
composer install
```

## Getting Started
*AppSkeleton* is designed to be easy to use and extend.
After installing the framework, you can create a new controller by extending the `AppController` class and defining your routes in the `routes.php` file.
You can then create your views using the built-in templating engine and access your models using the database abstraction layer.

Here's an example of a simple controller:

```php
<?php

use App\Core\AppController;

class HomeController extends AppController
{
    public function index()
    {
        $this->view('home/index', ['title' => 'Welcome to AppSkeleton']);
    }
}
```

In this example, we've created a new controller called `HomeController` that extends the `AppController` class.
We've defined a single method called `index` that renders a view called `home/index` and passes in a variable called title.

## Documentation

For more information on how to use `AppSkeleton`, please refer to the official documentation, located in the *docs/* directory of the framework.
Additionally, you can browse the source code for examples of how to use the various features and libraries provided by the framework.

## History

The AppSkeleton was created by [@akozeka]( https://github.com/akozeka ) back in 2006 and based on Anahoret PHP Lib also known as [torba]( https://sourceforge.net/projects/torba ).


## Contributing
If you find a bug or would like to contribute to the framework, please open an issue or submit a pull request on GitHub.
We welcome all contributions and appreciate your help in making `AppSkeleton` even better!
