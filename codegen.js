import fs from 'fs';
import path from 'path';

// Function to read the MySQL dump file
function readMySQLDump(filePath) {
    return fs.readFileSync(filePath, 'utf8');
}


// Function to parse the MySQL dump and extract table names, column definitions, and nullability
function parseMySQLDump(mysqlDump) {
    const tableRegex = /CREATE TABLE `(\w+)` \(([\s\S]+?)\)(?:,|;)/g;
    const columnRegex = /`(\w+)` (\w+)(?:\((\d+)\))?([^,]*)(?:,|$)/g;

    const tables = [];

    let match;
    while ((match = tableRegex.exec(mysqlDump)) !== null) {

        const tableName = match[1];
        const columns = [];

        let columnMatch;
        while ((columnMatch = columnRegex.exec(match[2])) !== null) {
            const columnName = columnMatch[1];
            const columnType = columnMatch[2];
            const nullable = !columnMatch[4].includes('NOT NULL'); // Check if column is nullable
            columns.push({ name: columnName, type: columnType, nullable });
        }

        tables.push({ name: tableName, columns });
    }

    return tables;
}



function capitalizeFirstLetter(str) {
    return str.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function generateInterfaces(tables) {
    let output = '';

    tables.forEach(table => {
        output += `export interface ${capitalizeFirstLetter(table.name)} {\n`;
        table.columns.forEach(column => {
            const columnName = column.name;
            const nullableSymbol = column.nullable ? '?' : '';
            const columnType = mapMySQLTypeToTypeScript(column.type);
            output += `    ${columnName}${nullableSymbol}: ${columnType};\n`;
        });
        output += `}\n\n`;
    });

    return output;
}

// Function to generate TypeScript input interfaces
function generateInputInterfaces(tables) {
    let output = '';

    tables.forEach(table => {
        output += `export interface ${capitalizeFirstLetter(table.name)}Input {\n`;
        table.columns.forEach(column => {
            if (column.name !== 'id') { // Exclude auto-incremented primary key from input interfaces
                const columnName = column.name;
                const nullableSymbol = column.nullable ? '?' : '';
                const columnType = mapMySQLTypeToTypeScript(column.type);
                output += `    ${columnName}${nullableSymbol}: ${columnType};\n`;
            }
        });
        output += `}\n\n`;
    });

    return output;
}


// Function to map MySQL data types to TypeScript types
function mapMySQLTypeToTypeScript(mysqlType) {
    // Add more mappings as needed
    switch (mysqlType.toUpperCase()) {
        case 'INT':
            return 'number';
        case 'VARCHAR':
            return 'string';
        case 'TEXT':
            return 'string';
        // Handle other types accordingly
        default:
            return 'any';
    }
}

// Function to ensure directory exists
function ensureDirectoryExists(filePath) {
    const dirname = path.dirname(filePath);
    if (!fs.existsSync(dirname)) {
        fs.mkdirSync(dirname, { recursive: true });
    }
}

// Main function
function generateTypeScriptInterfaces(mysqlDumpPath, outputPath) {
    const mysqlDump = readMySQLDump(mysqlDumpPath);
    const tables = parseMySQLDump(mysqlDump);
    const tsInterfaces = generateInterfaces(tables);
    const tsInputInterfaces = generateInputInterfaces(tables);

    ensureDirectoryExists(outputPath);
    fs.writeFileSync(outputPath, tsInterfaces + tsInputInterfaces);
}

// Example usage
const mysqlDumpPath = 'meta.sql';
const outputPath = 'src/interfaces/index.ts';
generateTypeScriptInterfaces(mysqlDumpPath, outputPath);
