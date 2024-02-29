import fs from 'fs';
import path from 'path';

// Function to read the MySQL dump file
function readMySQLDump(filePath) {
    return fs.readFileSync(filePath, 'utf8');
}

// Function to parse the MySQL dump and extract table names and column definitions
function parseMySQLDump(mysqlDump) {
    const tableRegex = /CREATE TABLE `(\w+)` \(([\s\S]+?)\)(?:,|;)/g;
    const columnRegex = /`(\w+)` (\w+)(?:\((\d+)\))?[^,]*(?:,|$)/g;

    const tables = [];

    let match;
    while ((match = tableRegex.exec(mysqlDump)) !== null) {
        const tableName = match[1];
        const columns = [];

        let columnMatch;
        while ((columnMatch = columnRegex.exec(match[2])) !== null) {
            const columnName = columnMatch[1];
            const columnType = columnMatch[2];
            columns.push({ name: columnName, type: columnType });
        }

        tables.push({ name: tableName, columns });
    }

    return tables;
}

// Function to generate TypeScript interfaces
function generateInterfaces(tables) {
    let output = '';

    tables.forEach(table => {
        output += `interface ${table.name} {\n`;
        table.columns.forEach(column => {
            output += `    ${column.name}: ${mapMySQLTypeToTypeScript(column.type)};\n`;
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

    // Ensure directory exists before writing the file
    ensureDirectoryExists(outputPath);

    fs.writeFileSync(outputPath, tsInterfaces);
}

// Example usage
const mysqlDumpPath = 'meta.sql';
const outputPath = 'interfaces.ts';
generateTypeScriptInterfaces(mysqlDumpPath, outputPath);
