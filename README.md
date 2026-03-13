# GC-SBR / 1504-SIG Application

A Laravel-based application designed for managing and verifying business units and regional data (SBR - Statistical Business Register).

## Key Features

- **Unit Management**: Efficiently add, edit, and track business units across different regions.
- **Regional Drill-down**: Hierarchical view of regions (Provinsi, Kabupaten, Kecamatan, Desa) for better data organization.
- **Data Verification**: Tools to verify and validate unit data to ensure accuracy in the register.
- **Bulk Import/Export**: Support for processing large datasets via Excel/CSV for faster updates.
- **Real-time Statistics**: Dashboard providing insights into current progress and data accumulation.

## Application Structure

- **Backend**: Built with Laravel 10+, utilizing Eloquent ORM for data management.
- **Frontend**: Responsive UI using Blade templates and custom CSS/JS for interactive elements.
- **Routing**: Clean and structured API and web routes for unit and regional operations.

## Setup Instructions

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/pandu2406/1504-sig.git
    ```
2.  **Install dependencies**:
    ```bash
    composer install
    npm install
    ```
3.  **Environment Setup**:
    - Copy `.env.example` to `.env`.
    - Configure your database settings.
    - Run `php artisan key:generate`.
4.  **Database Migration**:
    ```bash
    php artisan migrate
    ```
5.  **Run the application**:
    ```bash
    php artisan serve
    ```

## Notes
- This repository has been cleaned of map data and temporary database files for deployment.
- Ensure SQL library or compatible database engine is configured in your `.env`.
