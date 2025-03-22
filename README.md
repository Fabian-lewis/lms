# Land Management System (LMS)

## Overview
The **Land Management System (LMS)** is a web-based platform designed to streamline land-related processes, such as land searches, lease rate payments, land sales, and land mutation requests. The system caters to different user roles, including general users, surveyors, and ministry officials.

## Features
### **1. User Roles & Access**
- **General Users:**
  - Perform land searches.
  - View and list land parcels for sale.
  - Pay lease rates.
  - Access registered surveyors for land mutation services.
- **Surveyors:**
  - Submit land division and ownership mutation forms.
  - Track mutation request statuses (pending, approved, rejected).
  - Use an interactive map to define land divisions.
- **Ministry Officials:**
  - Review and approve/reject mutation requests.
  - Issue new title deeds for approved requests.
  - Manage lease rate payments and notifications.

### **2. Land Search Report**
- Users enter an ID, land owner's ID, and title deed number to conduct a search.
- Displays the owner's details, land size, and coordinates.
- Uses **GeoJSON** to visualize land parcels on a map.
- Notifies owners when someone searches their land.

### **3. Payment System**
- Integrates **M-Pesa Daraja API** for secure transactions.
- Users receive receipts with details like land parcel, amount paid, date, and owner information.

### **4. Land Mutation & Ownership Transfer**
- Surveyors submit mutation requests with interactive maps.
- Ministry officials review and approve/reject requests.
- Approved mutations trigger new title deed issuance.

### **5. Notifications System**
- Email notifications for important actions.
- Potential SMS integration for lease rate reminders.

## Tech Stack
- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** PostgreSQL
- **APIs & Tools:** Google Maps API, M-Pesa Daraja API, GeoJSON

## Installation & Setup
### **1. Clone the Repository**
```sh
 git clone https://github.com/your-username/LMS.git
 cd LMS
```
### **2. Set Up the Database**
- Install **PostgreSQL** and create a database.
- Import the SQL schema:
```sh
 psql -U your_user -d your_database -f database/schema.sql
```
### **3. Configure Environment Variables**
Create a `.env` file and add:
```env
DB_HOST=your_db_host
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASS=your_db_password
MPESA_API_KEY=your_mpesa_api_key
```
### **4. Run the Application**
```sh
 php -S localhost:8000
```
Access the system via `http://localhost:8000`

## Future Enhancements
- PDF reports for land searches.
- SMS notifications.
- AI-based fraud detection for land ownership disputes.
- Mobile app integration.

## License
This project is licensed under the MIT License.

---
**Author:** Fabian Ndung'u  
**Contact:** fabitolewi@gmail.com 

