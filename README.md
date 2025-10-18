# Timecard System

This project is a timecard management system designed for full-time employees. It allows users to record their working hours and manage their timecards efficiently.

## Project Structure

- `src/app.ts`: Entry point of the application. Sets up the Express app instance and configures middleware and routes.
- `src/controllers/timecardController.ts`: Exports the `TimecardController` class, which contains methods for managing timecards (e.g., `recordTime`, `getTimecards`).
- `src/models/timecardModel.ts`: Defines the `Timecard` model, representing the structure of timecards stored in the database and providing methods for CRUD operations.
- `src/routes/timecardRoutes.ts`: Exports the `setTimecardRoutes` function, which sets up routes related to timecards using the `TimecardController`.
- `src/db/index.ts`: Handles database connection setup, establishing a connection to the new database and performing necessary initial configurations.
- `src/types/index.ts`: Exports interfaces used in the timecard system (e.g., `Timecard` interface) for type-safe data manipulation.
- `tsconfig.json`: TypeScript configuration file specifying compiler options and files to be compiled.
- `package.json`: npm configuration file listing project dependencies and scripts.

## Installation

To install the project dependencies, run:

```
npm install
```

## Usage

To start the application, use the following command:

```
npm start
```

Ensure that your database is properly configured before running the application.

## License

This project is licensed under the MIT License.