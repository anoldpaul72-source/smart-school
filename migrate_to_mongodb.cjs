/**
 * MySQL SQL Dump → MongoDB Atlas Migration Script
 * 
 * This script:
 * 1. Parses the MySQL SQL dump file
 * 2. Extracts all INSERT data from each table
 * 3. Converts MySQL integer IDs to MongoDB-friendly format
 * 4. Inserts into MongoDB Atlas collections
 * 
 * Usage: node migrate_to_mongodb.cjs
 * 
 * Requires: npm install mongodb
 */

const { MongoClient } = require('mongodb');
const fs = require('fs');
const path = require('path');

// ============================================================
// CONFIGURATION - Update these before running
// ============================================================
const MONGODB_URI = process.env.MONGODB_URI;
const DB_NAME = process.env.DB_DATABASE || 'smart-results';
const SQL_FILE = path.join(__dirname, 'if0_42048510_schoolresults.sql');

// ============================================================
// SQL PARSER
// ============================================================

function parseSQLDump(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const tables = {};

    // Find all INSERT INTO statements
    const insertRegex = /INSERT INTO `(\w+)` \(([^)]+)\) VALUES\s*([\s\S]*?);/g;
    let match;

    while ((match = insertRegex.exec(content)) !== null) {
        const tableName = match[1];
        const columns = match[2].split(',').map(c => c.trim().replace(/`/g, ''));
        const valuesBlock = match[3];

        if (!tables[tableName]) {
            tables[tableName] = { columns, rows: [] };
        }

        // Parse value tuples - handle nested parentheses and quoted strings
        const rows = parseValueTuples(valuesBlock, columns);
        tables[tableName].rows.push(...rows);
    }

    return tables;
}

function parseValueTuples(valuesBlock, columns) {
    const rows = [];
    let i = 0;
    const str = valuesBlock.trim();

    while (i < str.length) {
        // Find opening parenthesis
        if (str[i] === '(') {
            let depth = 1;
            let start = i + 1;
            i++;
            let values = [];
            let currentVal = '';
            let inString = false;
            let stringChar = '';
            let escaped = false;

            while (i < str.length && depth > 0) {
                const ch = str[i];

                if (escaped) {
                    currentVal += ch;
                    escaped = false;
                    i++;
                    continue;
                }

                if (ch === '\\') {
                    escaped = true;
                    currentVal += ch;
                    i++;
                    continue;
                }

                if (inString) {
                    if (ch === stringChar) {
                        // Check for escaped quote ('')
                        if (i + 1 < str.length && str[i + 1] === stringChar) {
                            currentVal += ch + ch;
                            i += 2;
                            continue;
                        }
                        inString = false;
                    }
                    currentVal += ch;
                    i++;
                    continue;
                }

                if (ch === '\'' || ch === '"') {
                    inString = true;
                    stringChar = ch;
                    currentVal += ch;
                    i++;
                    continue;
                }

                if (ch === ',' && depth === 1) {
                    values.push(currentVal.trim());
                    currentVal = '';
                    i++;
                    continue;
                }

                if (ch === '(') {
                    depth++;
                }
                if (ch === ')') {
                    depth--;
                    if (depth === 0) {
                        values.push(currentVal.trim());
                        break;
                    }
                }

                currentVal += ch;
                i++;
            }

            // Convert values to a document
            const doc = {};
            for (let j = 0; j < columns.length && j < values.length; j++) {
                doc[columns[j]] = parseValue(values[j]);
            }
            rows.push(doc);
        }
        i++;
    }

    return rows;
}

function parseValue(val) {
    if (val === 'NULL' || val === 'null') return null;

    // Remove surrounding quotes
    if ((val.startsWith("'") && val.endsWith("'")) || (val.startsWith('"') && val.endsWith('"'))) {
        val = val.slice(1, -1);
        // Unescape
        val = val.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\').replace(/\\n/g, '\n').replace(/\\r/g, '\r');
        return val;
    }

    // Try number
    if (/^-?\d+$/.test(val)) return parseInt(val, 10);
    if (/^-?\d+\.\d+$/.test(val)) return parseFloat(val);

    return val;
}

// ============================================================
// MONGODB MIGRATION
// ============================================================

async function migrate() {
    console.log('========================================');
    console.log('  MySQL → MongoDB Atlas Migration');
    console.log('========================================\n');

    // Parse SQL dump
    console.log(`📄 Parsing SQL dump: ${SQL_FILE}`);
    const tables = parseSQLDump(SQL_FILE);

    const tableNames = Object.keys(tables);
    console.log(`✅ Found ${tableNames.length} tables: ${tableNames.join(', ')}\n`);

    for (const [name, data] of Object.entries(tables)) {
        console.log(`   ${name}: ${data.rows.length} rows`);
    }

    // Connect to MongoDB
    console.log(`\n🔌 Connecting to MongoDB Atlas...`);
    const client = new MongoClient(MONGODB_URI);

    try {
        await client.connect();
        console.log('✅ Connected to MongoDB Atlas\n');

        const db = client.db(DB_NAME);

        // Migrate each table
        for (const [tableName, data] of Object.entries(tables)) {
            if (data.rows.length === 0) {
                console.log(`⏭️  Skipping ${tableName} (0 rows)`);
                continue;
            }

            const collection = db.collection(tableName);

            // Drop existing collection to avoid duplicates
            try {
                await collection.drop();
                console.log(`🗑️  Dropped existing collection: ${tableName}`);
            } catch (e) {
                // Collection doesn't exist, that's fine
            }

            // Insert documents
            const result = await collection.insertMany(data.rows);
            console.log(`✅ ${tableName}: inserted ${result.insertedCount} documents`);
        }

        // Create indexes for performance
        console.log('\n📇 Creating indexes...');

        // Users indexes
        await db.collection('users').createIndex({ username: 1 }, { unique: true });
        await db.collection('users').createIndex({ role: 1 });
        await db.collection('users').createIndex({ school_name: 1 });
        console.log('   ✅ users indexes created');

        // Students indexes
        await db.collection('students').createIndex({ student_id: 1 });
        await db.collection('students').createIndex({ school_name: 1 });
        await db.collection('students').createIndex({ class_name: 1 });
        await db.collection('students').createIndex({ parent_id: 1 });
        await db.collection('students').createIndex({ reg_number: 1 });
        console.log('   ✅ students indexes created');

        // Marks indexes
        await db.collection('marks').createIndex({ student_id: 1 });
        await db.collection('marks').createIndex({ subject_id: 1 });
        await db.collection('marks').createIndex({ teacher_id: 1 });
        await db.collection('marks').createIndex({ year: 1, term: 1 });
        console.log('   ✅ marks indexes created');

        // Attendance indexes
        await db.collection('attendance').createIndex({ student_id: 1 });
        await db.collection('attendance').createIndex({ attendance_date: 1 });
        await db.collection('attendance').createIndex({ class_name: 1 });
        console.log('   ✅ attendance indexes created');

        // Subjects indexes
        await db.collection('subjects').createIndex({ subject_name: 1 }, { unique: true });
        console.log('   ✅ subjects indexes created');

        // Teacher assignments indexes
        await db.collection('teacher_assignments').createIndex({ teacher_id: 1 });
        await db.collection('teacher_assignments').createIndex({ subject_id: 1 });
        console.log('   ✅ teacher_assignments indexes created');

        // Timetables indexes
        await db.collection('timetables').createIndex({ school_name: 1, class_name: 1 });
        await db.collection('timetables').createIndex({ day_of_week: 1 });
        console.log('   ✅ timetables indexes created');

        // Student payments indexes
        await db.collection('student_payments').createIndex({ student_id: 1 });
        console.log('   ✅ student_payments indexes created');

        // Fee structures indexes
        await db.collection('fee_structures').createIndex({ class_name: 1, academic_year: 1 });
        console.log('   ✅ fee_structures indexes created');

        // Schools indexes
        await db.collection('schools').createIndex({ school_name: 1 }, { unique: true });
        console.log('   ✅ schools indexes created');

        console.log('\n========================================');
        console.log('  ✅ Migration Complete!');
        console.log(`  Database: ${DB_NAME}`);
        console.log(`  Collections: ${tableNames.length}`);
        console.log('========================================\n');

    } catch (error) {
        console.error('❌ Migration failed:', error.message);
        throw error;
    } finally {
        await client.close();
        console.log('🔌 MongoDB connection closed');
    }
}

migrate().catch(console.error);
