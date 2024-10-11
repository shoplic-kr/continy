# CHANGELOG

## 0.2.0

- Support optional second argument of get() method. 
  The argument is a closure that returns an array for the class constructor.
- Support call() method, supporting dependency injection for function or method parameters.
- Unit testing is based on wp-tests.


## 0.1.5

- Add codestyle.xml
- Add tests
- Fix line ending of bin/install-wp-tests.sh


## 0.1.4

- Remove module, and support interface (use shoplic-kr/interface instead.)


## 0.1.3

- Fix error when Continy calls closures
- Add CHANGELOG.md


## 0.1.2
2024-07-24

- Add interface


## 0.1.1
2024-07-18

- Fix return type of Continy::get()