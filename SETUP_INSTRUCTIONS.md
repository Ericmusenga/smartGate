# Setup Instructions - Fix Login Issues

## 🚨 **Current Issue**
You're getting "Invalid username or password" for all users because the database hasn't been set up properly.

## 🔧 **Step-by-Step Solution**

### **Step 1: Start XAMPP Services**
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Make sure both show green status

### **Step 2: Setup Database (Automatic)**
1. Open your browser
2. Go to: `http://localhost/Capstone_project/setup_database.php`
3. Click "Yes, recreate" if prompted
4. Wait for the setup to complete
5. You should see "✅ Database Setup Complete!"

### **Step 3: Test Database Connection**
1. Go to: `http://localhost/Capstone_project/test_database.php`
2. Verify all tables exist and have data
3. Check that users are present in the database

### **Step 4: Test Login**
1. Go to: `http://localhost/Capstone_project/login.php`
2. Try these credentials:

#### **Admin User:**
- Username: `admin`
- Password: `admin123`

#### **Student User:**
- Username: `2023/001`
- Password: `2023/001`

#### **Security Officer:**
- Username: `SEC001`
- Password: `SEC001`

## 🔍 **If Still Having Issues**

### **Manual Database Setup (if automatic fails):**

1. **Open phpMyAdmin:**
   - Go to: `http://localhost/phpmyadmin`

2. **Create Database:**
   - Click "New" on the left sidebar
   - Enter database name: `gate_management_system`
   - Click "Create"

3. **Import Schema:**
   - Select the `gate_management_system` database
   - Click "Import" tab
   - Click "Choose File"
   - Select: `Capstone_project/database/schema.sql`
   - Click "Go" to import

4. **Verify Import:**
   - Check that all tables were created
   - Verify sample data is present

## 📋 **What the Setup Script Does**

The `setup_database.php` script automatically:

1. ✅ Connects to MySQL
2. ✅ Creates the `gate_management_system` database
3. ✅ Imports the complete schema with tables
4. ✅ Inserts sample data (users, roles, students, security officers)
5. ✅ Creates hashed passwords for all users
6. ✅ Sets up proper relationships between tables

## 🎯 **Expected Results**

After successful setup, you should have:

- **3 Roles:** admin, security, student
- **3 Users:** admin, student, security officer
- **1 Student:** with registration number 2023/001
- **1 Security Officer:** with security code SEC001
- **All tables:** users, roles, students, security_officers, etc.

## 🔐 **Login Flow**

1. **First Login:** User enters credentials → Redirected to change password
2. **Password Change:** User sets new password → Redirected to dashboard
3. **Subsequent Logins:** User enters credentials → Direct to dashboard

## 🆘 **Troubleshooting**

### **If you get database connection errors:**
- Make sure MySQL is running in XAMPP
- Check that the database name is correct
- Verify username/password in `config/database.php`

### **If you get "Table doesn't exist" errors:**
- Run the setup script again
- Check that the schema file exists at `database/schema.sql`

### **If login still fails:**
- Check the test page to see what users exist
- Verify passwords are properly hashed
- Check error logs in XAMPP

## 📞 **Need Help?**

If you're still having issues:
1. Run the test script and share the output
2. Check XAMPP error logs
3. Make sure all files are in the correct locations

---

**🎉 Once setup is complete, your login system will work perfectly!** 