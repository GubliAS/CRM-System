# SOFTWARE REQUIREMENTS SPECIFICATION 

Customer Relationship Management (CRM) System 

Version 1.0 

**Document Date:** 10/9/2025 

**Document Status:** Final for Development 

|Project Name|CRM System - Sales & Service Cloud|
|---|---|
|Target Platform|Web Application (Desktop & Mobile Responsive)|
|Technology Stack|Laravel, HTML, PHP, MYSQL|
|Database|Relational database with support for complex relationships|



## TABLE OF CONTENTS 

1. INTRODUCTION 

1.1 Purpose 

1.2 Scope 

1.3 Definitions and Acronyms 

1.4 References 

2. OVERALL DESCRIPTION 

2.1 Product Perspective 

2.2 Product Functions 

2.3 User Characteristics 

2.4 Constraints 

3. SYSTEM ARCHITECTURE 

3.1 System Overview 

3.2 Data Model 

4. FUNCTIONAL REQUIREMENTS 

4.1 User Authentication & Authorization 

4.2 Home Dashboard 

4.3 Lead Management 

4.4 Account Management 

4.5 Contact Management 

4.6 Opportunity Management 

4.7 Case Management 

4.8 Task Management 

4.9 Calendar & Events 

4.10 Reports 

4.11 Dashboards 

4.12 Search Functionality 

5. NON-FUNCTIONAL REQUIREMENTS 

5.1 Performance Requirements 

5.2 Security Requirements 

5.3 Usability Requirements 

5.4 Reliability & Availability 

5.5 Scalability 

6. USER INTERFACE REQUIREMENTS 

7. DATA REQUIREMENTS 

8. INTEGRATION REQUIREMENTS 

9. APPENDICES 

## 1. INTRODUCTION 

### 1.1 Purpose 

This Software Requirements Specification (SRS) document provides a complete description of all functions and specifications for the Customer Relationship Management (CRM) system. This document is intended for: 

- • Development team members who will implement the system 

- • Quality assurance team for test planning and execution 

- • Project managers for planning and resource allocation 

- • Stakeholders for review and approval 

### 1.2 Scope 

The CRM system is a comprehensive web-based application designed to manage customer relationships, sales processes, and customer service operations. The system encompasses: 

Sales Cloud Functionality: 

- • Lead tracking and conversion management 

- • Account and contact management 

- • Opportunity pipeline management with sales stages 

- • Sales forecasting and analytics 

Service Cloud Functionality: 

- • Case management for customer support 

- • Ticket routing and escalation 

Productivity Features: 

- • Task management with reminders 

- • Calendar and event scheduling 

- • Comprehensive reporting and dashboard analytics 

### 1.3 Definitions and Acronyms 

|Term|Definition|
|---|---|
|CRM|Customer Relationship Management|
|Lead|A potential customer or prospect who has shown interest in products/services|
|Account|An organization or company with which the business has a relationship|
|Contact|An individual person associated with an Account|
|Opportunity|A qualified sales deal in progress with potential revenue|
|Case|A customer service request or support ticket|
|Pipeline|Collection of opportunities at various stages of the sales process|
|Dashboard|Visual display of key metrics and reports|
|SRS|Software Requirements Specification|
|UI|User Interface|
|API|Application Programming Interface|



### 1.4 References 

This requirements document is based on analysis of Salesforce Lightning Experience interface and functionality observed in January 2022. The implementation should replicate observed features while maintaining platform independence. 

## 2. OVERALL DESCRIPTION 

### 2.1 Product Perspective 

The CRM system is a standalone web application designed to be accessed through modern web browsers. The system consists of: 

- • Front-end web application with responsive design for desktop and mobile access 

- • Back-end API server handling business logic and data processing 

- • Relational database for data persistence 

- • File storage system for documents and attachments 

- • Email integration capabilities for notifications and communications 

### 2.2 Product Functions 

The major functions of the CRM system include: 

Customer Data Management: 

- • Centralized storage of customer information (Leads, Accounts, Contacts) 

- • Relationship tracking between contacts and accounts 

- • Activity history and interaction logging 

Sales Process Management: 

- • Lead qualification and conversion workflow 

- • Opportunity pipeline visualization and stage management 

- • Sales forecasting based on opportunity data 

- • Revenue tracking and reporting 

Customer Service Management: 

- • Case creation and assignment 

- • Case status tracking and resolution workflow 

- • Priority-based case management 

Activity Management: 

- • Task creation, assignment, and completion tracking 

- • Calendar view for events and appointments 

- • Reminder notifications for due tasks and upcoming events 

Analytics and Reporting: 

- • Pre-built reports for common business metrics 

- • Custom report builder with filters and grouping 

- • Dashboard creation with multiple visualizations 

- • Export capabilities for reports 

### 2.3 User Characteristics 

The system is designed for the following user types: 

Sales Representatives: 

- • Primary users managing leads, opportunities, and customer relationships 

- • Moderate technical proficiency expected 

- • Need intuitive interface for daily data entry and updates 

Sales Managers: 

- • Monitor team performance and pipeline health 

- • Heavy users of reporting and dashboard features 

- • Require forecast accuracy and revenue projections 

Customer Service Representatives: 

- • Manage customer support cases and inquiries 

- • Need quick access to customer history and account information 

- • Focus on case resolution time and customer satisfaction 

System Administrators: 

- • Configure system settings and user permissions 

- • Manage data integrity and system maintenance 

- • High technical proficiency with system administration experience 

### 2.4 Constraints 

Regulatory Constraints: 

- • System must comply with GDPR for data privacy (if applicable in jurisdiction) 

- • Data encryption requirements for sensitive customer information 

- • Audit trail requirements for data access and modifications 

Technical Constraints: 

- • Must support modern web browsers (Chrome, Firefox, Safari, Edge - current and previous version) 

- • Mobile responsive design for tablet and smartphone access 

- • System should handle concurrent users without performance degradation 

Business Constraints: 

- • Implementation should follow industry best practices for CRM systems 

- • User interface should provide familiar patterns to minimize training requirements 

## 3. SYSTEM ARCHITECTURE 

### 3.1 System Overview 

The CRM system follows a three-tier architecture: 

Presentation Layer: 

- • Single-page application (SPA) architecture for responsive user experience 

- • Component-based UI framework for modular development 

- • Client-side routing for seamless navigation 

- • State management for efficient data handling 

Application Layer: 

- • RESTful API for all data operations 

- • Business logic processing and validation 

- • Authentication and authorization services 

- • Background job processing for scheduled tasks and notifications 

Data Layer: 

- • Relational database management system (RDBMS) 

- • Normalized data structure with referential integrity 

- • Database indexing for optimized query performance 

- • Backup and recovery mechanisms 

### 3.2 Data Model 

Core Entities and Relationships: 

Lead Entity: 

- • Represents potential customers before conversion 

- • Can be converted to Account, Contact, and Opportunity 

- • One-to-many relationship with Tasks and Events 

Account Entity: 

- • Represents organizations/companies 

- • One-to-many relationship with Contacts 

- • One-to-many relationship with Opportunities 

- • One-to-many relationship with Cases 

- • Optional self-referential relationship for parent accounts 

Contact Entity: 

- • Represents individual people 

- • Many-to-one relationship with Account (required) 

- • Optional many-to-one reporting relationship to another Contact 

- • One-to-many relationship with Cases 

Opportunity Entity: 

- • Represents sales deals 

- • Many-to-one relationship with Account (required) 

- • Belongs to sales stage workflow 

- • Contains amount and probability fields for forecasting 

Case Entity: 

- • Represents customer service requests 

- • Optional many-to-one relationship with Account 

- • Optional many-to-one relationship with Contact 

- • Status workflow from New to Closed 

Task Entity: 

- • Represents to-do items 

- • Polymorphic relationship - can be related to Lead, Account, Contact, Opportunity, or Case 

- • Assigned to a User 

- • Contains due date and reminder settings 

Event Entity: 

- • Represents calendar appointments 

- • Polymorphic relationship - can be related to Account, Contact, Lead, or Opportunity 

- • Contains start and end date/time 

- • Support for all-day events and recurring events 

User Entity: 

- • System users who access the application 

- • Owner relationship to all major entities 

- • Associated with roles and permissions 

## 4. FUNCTIONAL REQUIREMENTS 

### 4.1 User Authentication & Authorization 

FR-AUTH-001: User Login 

Description: System shall provide secure user authentication 

Requirements: 

- • Username and password authentication 

- • Password must meet complexity requirements (minimum 8 characters, mix of upper/lower case, numbers) 

- • Account lockout after 5 failed login attempts 

- • Session timeout after 2 hours of inactivity 

- • Remember me option (optional 30-day persistent session) 

FR-AUTH-002: Password Reset 

Description: Users shall be able to reset forgotten passwords 

Requirements: 

- • Forgot password link on login page 

- • Email verification for password reset 

- • Time-limited password reset token (valid for 1 hour) 

- • Cannot reuse last 5 passwords 

FR-AUTH-003: Role-Based Access Control 

Description: System shall enforce role-based permissions 

Requirements: 

- • Minimum roles: System Administrator, Sales Manager, Sales Representative, Service Representative, Read-Only User 

- • Object-level permissions (Create, Read, Update, Delete) for each entity type 

- • Record-level sharing rules (Private, Public Read Only, Public Read/Write) 

- • Users can only see records they own or have been granted access to 

### 4.2 Home Dashboard 

FR-HOME-001: Dashboard Layout 

Description: Home page shall display relevant information and quick access to key functions 

Requirements: 

- • Top navigation bar with app switcher, search, and user profile menu 

- • Main navigation tabs: Home, Leads, Accounts, Contacts, Opportunities, Cases, Tasks, Calendar, Reports, Dashboards 

- • Dashboard grid with configurable widget placement 

- • Recent Records section showing last 5 accessed items 

- • Assistant panel showing recommended actions and inactive accounts 

FR-HOME-002: Pipeline Visualization 

Description: Display current year sales pipeline as funnel chart 

Requirements: 

- • Funnel chart showing opportunities by stage 

- • Stages: Qualification, Meeting Scheduled, Proposal/Price Quote, Negotiation/Review, Closed Won, Closed Lost 

- • Display total pipeline value 

- • Stage-wise breakdown with percentages 

- • Click through to view opportunities in each stage 

- • Filter by current year, fiscal year, or custom date range 

FR-HOME-003: Revenue Source Analysis 

Description: Display potential revenue breakdown by lead source 

Requirements: 

- • Donut/pie chart showing revenue by lead source 

- • Lead sources: Advertisement, External Referral, Social, Trade Show, Web, and custom sources 

- • Display total potential revenue in center 

- • Show value and percentage for each source 

- • Legend with source names and color coding 

- • Filterable by time period 

FR-HOME-004: Today's Tasks Widget 

Description: Display tasks due today for current user 

Requirements: 

- • List tasks with due date of today 

- • Show task subject, related record, and due time 

- • Ability to mark tasks complete from widget 

- • Display message when no tasks due today 

- • Link to full task list 

FR-HOME-005: Today's Events Widget 

Description: Display calendar events scheduled for today 

Requirements: 

- • List all events scheduled for current date 

- • Show event subject, time, and related record 

- • Display message when no events scheduled 

- • Link to full calendar view 

FR-HOME-006: Key Deals Widget 

Description: Display high-value or important opportunities 

Requirements: 

- • List recent opportunities with high probability or large amounts 

- • Show opportunity name, account, amount, close date, and stage 

- • Clickable links to opportunity details 

- • Link to view all opportunities 

FR-HOME-007: Assistant Recommendations 

Description: Intelligent recommendations based on data patterns 

Requirements: 

- • Identify accounts with no activity for 30+ days 

- • Identify opportunities nearing close date without updates 

- • Suggest follow-up actions 

- • Dismissable recommendations 

- • Quick action buttons to view or update records 

### 4.3 Lead Management 

FR-LEAD-001: Lead List View 

Description: Display list of leads with sorting and filtering 

Requirements: 

- • Default view: Recently Viewed leads 

- • Columns: Name, Title, Company, Phone, Email, Lead Source, Owner, Lead Status 

- • Sortable columns (ascending/descending) 

- • Column customization - show/hide columns 

- • Inline search within list 

- • Bulk actions: Change Owner, Change Status, Delete 

- • New Lead button in header 

- • Import leads functionality 

- • Add to Campaign mass action 

FR-LEAD-002: Create New Lead 

Description: Form to create new lead record 

Required Fields: 

- • Last Name (required) 

- • Company (required) 

- • Lead Status (required, default: New) 

Optional Fields: 

- • Salutation (dropdown: Mr., Ms., Mrs., Dr., Prof.) 

- • First Name 

- • Title 

- • Phone 

- • Mobile 

- • Email 

- • Website 

- • Lead Source (dropdown: Advertisement, External Referral, Social, Trade Show, Web, Other) 

- • Industry (dropdown: customizable list) 

- • Rating (dropdown: Hot, Warm, Cold) 

- • Annual Revenue 

- • Number of Employees 

- • Address fields: Street, City, State/Province, Zip/Postal Code, Country 

- • Description (text area) 

Form Actions: 

- • Save button - saves and returns to list view 

- • Save & New button - saves and opens new blank form 

- • Cancel button - closes form without saving 

FR-LEAD-003: Edit Lead 

Description: Modify existing lead information 

Requirements: 

- • Pre-populate form with existing data 

- • Same fields as create form 

- • Validation rules enforced on save 

- • Save button updates record 

- • Cancel button reverts changes 

FR-LEAD-004: Lead Detail View 

Description: Comprehensive view of lead information 

Requirements: 

- • Header with lead name and status 

- • Action buttons: Edit, Delete, Convert, Change Owner, Change Status 

- • Information sections: Lead Details, Address Information, Additional Information, System Information 

- • Related lists: Activity History, Tasks, Events, Notes & Attachments 

- • Quick actions: Log a Call, New Task, New Event, Email 

- • System fields: Created By, Created Date, Last Modified By, Last Modified Date 

FR-LEAD-005: Convert Lead 

Description: Convert qualified lead to Account, Contact, and optionally Opportunity 

Requirements: 

- • Convert Lead wizard/modal 

- • Option to match to existing Account (search by company name) 

- • Option to create new Account from lead data 

- • Automatically create Contact from lead information 

- • Checkbox to create Opportunity 

- • If creating Opportunity: Opportunity Name (required), Amount, Close Date, Stage 

- • Option to transfer open tasks and events to new Account/Contact/Opportunity 

- • Lead Status automatically changed to Converted 

- • Converted leads become read-only 

- • Maintain relationship between converted Lead and resulting records 

FR-LEAD-006: Lead Status Management 

Description: Track lead progression through qualification stages 

Standard Lead Statuses: 

- • New - initial status for new leads 

- • Working - actively being pursued 

- • Nurturing - not ready to buy, keeping warm 

- • Qualified - meets criteria for conversion 

- • Unqualified - does not meet criteria 

- • Converted - successfully converted to Account/Contact/Opportunity 

FR-LEAD-007: Lead Assignment 

Description: Assign leads to appropriate sales representatives 

Requirements: 

- • Change Owner action from list view or detail page 

- • Search and select new owner 

- • Option to transfer related tasks and events 

- • Option to send email notification to new owner 

- • Record ownership history in activity timeline 

### 4.4 Account Management 

FR-ACCT-001: Account List View 

Description: Display list of accounts with sorting and filtering 

Requirements: 

- • Default view: Recently Viewed accounts 

- • Columns: Account Name, Phone, Type, Industry, Annual Revenue, Owner 

- • Sortable columns 

- • Search within list 

- • Bulk actions: Change Owner, Delete 

- • New Account button 

- • Import accounts functionality 

FR-ACCT-002: Create New Account 

Description: Form to create new account record 

Required Fields: 

- • Account Name (required) 

Optional Fields: 

- • Parent Account (lookup to another Account) 

- • Phone 

- • Fax 

- • Website 

- • Type (dropdown: Customer, Prospect, Partner, Other) 

- • Industry (dropdown: customizable list) 

- • Employees (number) 

- • Annual Revenue (currency) 

- • Billing Address: Street, City, State/Province, Zip/Postal Code, Country 

- • Shipping Address: Street, City, State/Province, Zip/Postal Code, Country 

- • Checkbox: Copy Billing Address to Shipping Address 

- • Description (text area) 

Form Actions: 

- • Save 

- • Save & New 

- • Cancel 

FR-ACCT-003: Account Detail View 

Description: Comprehensive view of account information and related records 

Requirements: 

- • Header with account name 

- • Action buttons: Edit, Delete, Change Owner 

- • Information sections: Account Details, Address Information, Additional Information, System Information 

- • Related lists with create capabilities: 

   - - Contacts (list of contacts at this account) 

   - - Opportunities (sales deals with this account) 

   - - Cases (support cases for this account) 

   - - Activity History (completed tasks and events) 

   - - Open Activities (upcoming tasks and events) 

   - - Notes & Attachments 

- • Each related list shows inline new record creation 

- • Quick actions: New Contact, New Opportunity, New Case, New Task, Log a Call 

FR-ACCT-004: Account Hierarchy 

Description: Support parent-child relationships between accounts 

Requirements: 

- • Parent Account field on account record 

- • Account hierarchy visualization showing tree structure 

- • Roll-up summaries for child account data (total employees, total revenue) 

- • View All Accounts in Hierarchy action 

### 4.5 Contact Management 

FR-CONT-001: Contact List View 

Description: Display list of contacts with sorting and filtering 

Requirements: 

- • Default view: Recently Viewed contacts 

- • Columns: Name, Account Name, Title, Phone, Email, Owner 

- • Sortable and searchable 

- • Bulk actions available 

- • New Contact button 

FR-CONT-002: Create New Contact 

Required Fields: 

- • Last Name 

- • Account Name (required lookup to Account) 

Optional Fields: 

- • Salutation, First Name, Middle Name 

- • Title, Department 

- • Phone, Mobile, Home Phone, Other Phone 

- • Email, Fax 

- • Reports To (lookup to another Contact) 

- • Assistant, Asst. Phone 

- • Mailing Address and Other Address 

- • Lead Source, Birthdate 

- • Description 

FR-CONT-003: Contact Detail View 

Requirements: 

- • Display all contact fields 

- • Link to associated Account 

- • Related lists: Cases, Opportunities, Activity History, Open Activities 

- • Quick actions: Log a Call, New Task, New Event, Email 

### 4.6 Opportunity Management 

FR-OPP-001: Opportunity List View 

Description: Display list of sales opportunities 

Requirements: 

- • Default view: Recently Viewed 

- • Columns: Opportunity Name, Account Name, Amount, Close Date, Stage, Probability, Owner 

- • Stage color-coding for quick visual identification 

- • Recent records dropdown showing last 5 opportunities 

- • New Opportunity button 

- • Mass Update action 

FR-OPP-002: Create New Opportunity 

Required Fields: 

- • Opportunity Name 

- • Account Name (lookup) 

- • Close Date 

- • Stage (dropdown) 

Optional Fields: 

- • Amount (currency) 

- • Probability (percentage, auto-populated based on stage) 

- • Type (dropdown: New Business, Existing Business, Renewal) 

- • Lead Source 

- • Next Step (text) 

- • Description 

FR-OPP-003: Opportunity Stages 

Standard Sales Stages: 

- • Qualification (10% probability) 

- • Meeting Scheduled (20% probability) 

- • Proposal/Price Quote (65% probability) 

- • Negotiation/Review (80% probability) 

- • Closed Won (100% probability) 

- • Closed Lost (0% probability) 

Requirements: 

- • Probability auto-updates when stage changes 

- • Stage progression path visualization 

- • Stage history tracking 

FR-OPP-004: Opportunity Detail View 

Requirements: 

- • Stage path indicator at top showing current and completed stages 

- • Key metrics: Amount, Probability, Expected Revenue (Amount × Probability) 

- • Action buttons: Edit, Delete, Change Owner, Clone 

- • Related lists: Products, Quotes, Activity History, Open Activities 

- • System Information showing Created By, Last Modified By with timestamps 

FR-OPP-005: Edit Opportunity 

Requirements: 

- • Inline editing capability from detail view 

- • Full edit page with all fields 

- • Pre-populated with existing data 

- • Validation: Close Date cannot be in past, Amount must be positive 

FR-OPP-006: Opportunity Actions 

Requirements: 

- • Delete: Archive opportunity (with confirmation dialog) 

- • Change Owner: Reassign to different sales rep 

- • Clone: Create duplicate with option to include related records 

### 4.7 Case Management 

FR-CASE-001: Case List View 

Requirements: 

- • Columns: Case Number, Subject, Status, Priority, Date/Time Opened, Owner 

- • Filter views: My Open Cases, All Open Cases, Recently Closed Cases 

- • Priority color-coding (High=red, Medium=yellow, Low=green) 

- • New Case button 

FR-CASE-002: Create New Case 

Required Fields: 

- • Status (dropdown: New, Working, Escalated, Closed) 

- • Case Origin (dropdown: Phone, Email, Web, Chat) 

Optional Fields: 

- • Contact Name (lookup) 

- • Account Name (lookup or auto-populated from Contact) 

- • Subject (text) 

- • Description (text area) 

- • Internal Comments (text area) 

- • Type (dropdown: Question, Problem, Feature Request) 

- • Case Reason (dropdown: customizable) 

- • Priority (dropdown: High, Medium, Low) 

- • Web Email, Web Company, Web Name, Web Phone 

FR-CASE-003: Case Detail View 

Requirements: 

- • Case header with Number, Subject, Status 

- • Action buttons: Edit, Close Case, Change Owner, Change Status 

- • Timeline of case interactions 

- • Related lists: Emails, Activity History, Attachments 

- • Quick actions: Email, Log a Call, New Task 

FR-CASE-004: Case Status Workflow 

Status Values: 

- • New - initial status 

- • Working - being actively addressed 

- • Escalated - requires manager attention 

- • Closed - resolved 

Requirements: 

- • Status can be updated from list view or detail page 

- • Closed cases cannot be edited (read-only) 

- • Reopen action available for closed cases 

### 4.8 Task Management 

FR-TASK-001: Task List View 

Requirements: 

- • Default view: Recently Viewed 

- • Columns: Subject, Related To, Name, Due Date, Status, Priority 

- • Filter views: Open Tasks, Completed Tasks, Today's Tasks, Overdue Tasks 

- • Inline checkbox to mark tasks complete 

FR-TASK-002: Create New Task 

Required Fields: 

- • Subject 

Optional Fields: 

- • Assigned To (defaults to current user) 

- • Related To (polymorphic lookup: Account, Contact, Lead, Opportunity, Case) 

- • Name (Contact lookup) 

- • Due Date (date picker) 

- • Status (dropdown: Not Started, In Progress, Completed, Deferred) 

- • Priority (dropdown: High, Normal, Low) 

- • Comments (text area) 

Reminder Settings: 

- • Reminder Set (checkbox) 

- • Reminder Date (date picker) 

- • Reminder Time (time picker) 

FR-TASK-003: Task Notifications 

Requirements: 

- • Email notification when task assigned 

- • Popup/browser notification at reminder time 

- • Daily digest email for tasks due today 

- • Overdue task notifications 

### 4.9 Calendar & Events 

FR-CAL-001: Calendar Views 

Requirements: 

- • View options: Day, Week, Month, Table (list) 

- • Navigation: Today button, Previous/Next arrows 

- • Month mini-calendar for quick date jumping 

- • Color-coded events by type or calendar 

- • New Event button always visible 

- • Click on calendar to create event at that date/time 

FR-CAL-002: Create New Event 

Required Fields: 

- • Subject 

- • Start Date/Time 

- • End Date/Time 

Optional Fields: 

- • Assigned To (defaults to current user) 

- • Related To (polymorphic lookup: Account, Contact, Lead, Opportunity) 

- • Name (Contact lookup) 

- • All-Day Event (checkbox) 

- • Location (text) 

- • Show Time As (dropdown: Busy, Free, Out of Office) 

- • Private (checkbox - only visible to owner) 

- • Description (text area) 

FR-CAL-003: Event Detail and Edit 

Requirements: 

- • Click event in calendar to view details popup 

- • Quick edit from popup 

- • Full edit page for complex changes 

- • Delete event with confirmation 

- • Drag-and-drop to reschedule (change date/time) 

FR-CAL-004: My Calendars 

Requirements: 

- • My Events calendar (default) 

- • Toggle visibility of different calendar types 

- • Color coding by calendar 

### 4.10 Reports 

FR-RPT-001: Report List 

Requirements: 

- • Folder structure: Recent, Created by Me, Private Reports, Public Reports, All Reports 

- • Report table with columns: Report Name, Description, Folder, Created By, Created On 

- • Search reports by name or description 

- • New Report button 

- • Actions per report: Run, Edit, Delete, Subscribe, Export, Add to Dashboard, Favorite, Move 

FR-RPT-002: Pre-built Reports 

System shall include pre-configured reports: 

Lead Reports: 

- • Leads by Source This FY 

- • Leads Converted This FY 

- • Leads Created by Month 

- • New Leads This FY By Owner 

- • Conversion of New Leads This FY 

Opportunity Reports: 

- • All Pipeline - Current Year 

- • Potential Revenue Source - Current Year 

- • Avg Deal Size - Current FY 

- • Avg. Deal Length 

- • Closed (Won) Opportunities This FY 

- • Closed Won Opportunities by Owner 

Case Reports: 

- • Average Case Age 

- • Monthly Case Volume by Channel 

FR-RPT-003: Report Builder 

Requirements: 

- • Step 1: Select Report Type (Leads, Accounts, Opportunities, etc.) 

- • Step 2: Select columns to display 

- • Step 3: Add filters (field, operator, value) 

- • Step 4: Group results by field(s) 

- • Step 5: Add chart/visualization 

- • Preview report results before saving 

- • Save to folder with name and description 

FR-RPT-004: Run Report 

Requirements: 

- • Display results in table format 

- • Sortable columns 

- • Chart visualization if configured 

- • Show record count 

- • Drill-down to record details by clicking row 

- • Filter results further with inline filters 

- • Refresh data button 

FR-RPT-005: Export Reports 

Requirements: 

- • Export formats: CSV, Excel, PDF 

- • Export button on report results page 

- • Option to include chart in export 

- • File download to user's device 

FR-RPT-006: Report Subscriptions 

Requirements: 

- • Subscribe to receive report via email 

- • Schedule: Daily, Weekly, Monthly 

- • Select day/time for delivery 

- • Email includes report results as attachment 

- • Manage subscriptions - edit or delete 

### 4.11 Dashboards 

FR-DASH-001: Dashboard List 

Requirements: 

- • Folder structure: Recent, Created by Me, Private Dashboards, All Dashboards 

- • Dashboard cards/tiles showing preview 

- • New Dashboard button 

- • Actions: View, Edit, Delete, Clone 

FR-DASH-002: Create Dashboard 

Requirements: 

- • Dashboard properties: Name, Description, Folder 

- • Grid-based layout with drag-and-drop widget placement 

- • Add components from existing reports 

- • Widget types: Chart, Table, Metric, Gauge 

- • Resize widgets by dragging corners 

- • Up to 20 components per dashboard 

FR-DASH-003: View Dashboard 

Requirements: 

- • All widgets load simultaneously 

- • Click widget to drill into underlying report 

- • Refresh button to update all data 

- • Print dashboard view 

- • Auto-refresh option (every 5, 10, 30, 60 minutes) 

FR-DASH-004: Dashboard Filters 

Requirements: 

- • Global filters that apply to all dashboard components 

- • Common filters: Date Range, Owner, Team 

- • Filter controls at top of dashboard 

- • Filters persist during session 

### 4.12 Search Functionality 

FR-SRCH-001: Global Search 

Requirements: 

- • Search box in top navigation bar 

- • Search across all objects: Leads, Accounts, Contacts, Opportunities, Cases 

- • Real-time search suggestions as user types (minimum 2 characters) 

- • Instant results dropdown showing top 5 matches per object 

- • Full search results page with all matches 

- • Filter results by object type 

- • Search history - recent searches 

FR-SRCH-002: Search Fields 

Searchable fields by object: 

- • Leads: Name, Company, Email, Phone 

- • Accounts: Account Name, Phone, Website 

- • Contacts: Name, Email, Phone, Account Name 

- • Opportunities: Opportunity Name, Account Name, Amount 

- • Cases: Case Number, Subject, Description 

FR-SRCH-003: Advanced Search 

Requirements: 

- • Advanced search link from search results 

- • Field-specific search criteria 

- • Multiple filter conditions with AND/OR logic 

- • Date range filters 

- • Save search criteria for reuse 

## 5. NON-FUNCTIONAL REQUIREMENTS 

### 5.1 Performance Requirements 

NFR-PERF-001: Response Time 

- • Page load time: < 3 seconds for initial page load 

- • Form submission: < 2 seconds for save operations 

- • Search results: < 1 second for simple searches 

- • Report generation: < 5 seconds for reports with < 10,000 records 

- • Dashboard load: < 5 seconds for dashboards with up to 10 components 

NFR-PERF-002: Throughput 

- • Support 100 concurrent users without performance degradation 

- • Handle 1000+ transactions per hour during peak usage 

- • Database query optimization for tables with > 100,000 records 

NFR-PERF-003: Resource Utilization 

- • Client-side memory usage: < 200MB per browser tab 

- • API response payload: < 1MB for list views 

- • Image and file optimization to reduce bandwidth 

NFR-PERF-004: Scalability 

- • Architecture should support horizontal scaling 

- • Database should support replication and sharding if needed 

- • Stateless application servers for load balancing 

### 5.2 Security Requirements 

NFR-SEC-001: Data Encryption 

- • All data transmission must use HTTPS/TLS 1.2 or higher 

- • Passwords must be hashed using bcrypt or stronger algorithm 

- • Sensitive data at rest should be encrypted (PII, financial data) 

- • Database connection strings and API keys stored securely (encrypted configuration or secrets manager) 

NFR-SEC-002: Authentication 

- • Strong password policy enforced (min 8 characters, complexity requirements) 

- • Session tokens expire after 2 hours of inactivity 

- • Secure session management (HTTP-only cookies, SameSite attribute) 

- • Support for multi-factor authentication (MFA) optional but recommended 

- • Account lockout after 5 failed login attempts 

NFR-SEC-003: Authorization 

- • Role-based access control (RBAC) enforced at API level 

- • Principle of least privilege - users only see data they need 

- • Record-level security based on ownership and sharing rules 

- • Audit trail of all data access and modifications 

NFR-SEC-004: Input Validation 

- • Server-side validation for all user inputs 

- • Protection against SQL injection attacks (parameterized queries) 

- • XSS protection - sanitize user input displayed in UI 

- • CSRF protection for state-changing operations 

- • File upload restrictions (type, size validation) 

NFR-SEC-005: Compliance 

- • GDPR compliance for data privacy (if applicable) 

- • Data retention and deletion policies 

- • User consent management for data collection 

- • Right to data portability (export user data) 

- • Right to be forgotten (data deletion on request) 

### 5.3 Usability Requirements 

NFR-USE-001: User Interface 

- • Clean, modern interface following Material Design or similar design system 

- • Consistent navigation patterns throughout application 

- • Intuitive iconography with tooltips for clarity 

- • Clear visual hierarchy and information architecture 

- • Breadcrumb navigation for deep pages 

NFR-USE-002: Responsive Design 

- • Mobile-responsive layout for screens ≥ 320px width 

- • Tablet-optimized layout for medium screens 

- • Desktop-optimized layout for large screens 

- • Touch-friendly controls on mobile devices (min 44x44px tap targets) 

NFR-USE-003: Accessibility 

- • WCAG 2.1 Level AA compliance 

- • Keyboard navigation support for all functions 

- • Screen reader compatibility (ARIA labels and landmarks) 

- • Sufficient color contrast (4.5:1 for normal text) 

- • Text resize up to 200% without loss of functionality 

- • Alternative text for images and icons 

NFR-USE-004: Error Handling 

- • Clear, user-friendly error messages 

- • Validation errors displayed inline near relevant fields 

- • Confirmation dialogs for destructive actions (delete, permanent changes) 

- • Toast/snackbar notifications for successful operations 

- • Graceful handling of network errors with retry options 

NFR-USE-005: Learnability 

- • Quick Start Guide available from help menu 

- • Contextual help tooltips on complex features 

- • Sample data pre-loaded in trial/demo environments 

- • Consistent terminology and labeling 

### 5.4 Reliability & Availability 

NFR-REL-001: Uptime 

- • Target 99.5% uptime (excluding scheduled maintenance) 

- • Scheduled maintenance windows communicated 48 hours in advance 

- • Maintenance windows during off-peak hours 

NFR-REL-002: Fault Tolerance 

- • Graceful degradation when non-critical services unavailable 

- • Automatic retry for transient failures 

- • Circuit breaker pattern for external dependencies 

NFR-REL-003: Data Backup 

- • Automated daily database backups 

- • Backup retention for minimum 30 days 

- • Point-in-time recovery capability 

- • Backups stored in geographically separate location 

- • Backup restoration tested quarterly 

NFR-REL-004: Error Recovery 

- • Transaction rollback on failure to maintain data integrity 

- • User work preserved in draft state on unexpected errors 

- • Auto-save functionality for long-form content 

NFR-REL-005: Monitoring 

- • Application performance monitoring (APM) instrumentation 

- • Error logging and alerting 

- • Health check endpoints for infrastructure monitoring 

- • User activity logging for audit and troubleshooting 

### 5.5 Scalability 

NFR-SCAL-001: User Scalability 

- • Support growth from 50 to 500 users without architecture changes 

- • Horizontal scaling of application servers 

- • Load balancing across multiple application instances 

NFR-SCAL-002: Data Scalability 

- • Support databases with 10 million+ records 

- • Pagination for large result sets (max 200 records per page) 

- • Database indexing strategy for optimal query performance 

- • Archive old data to maintain performance (configurable retention) 

NFR-SCAL-003: Infrastructure 

- • Cloud-native architecture for elastic scaling 

- • Containerization for consistent deployment 

- • CDN for static assets to reduce server load 

- • Caching strategy for frequently accessed data 

## 6. USER INTERFACE REQUIREMENTS 

### 6.1 Layout Structure 

Header (Global Navigation): 

- • Company logo/branding (top left) 

- • Global search bar (center) 

- • Utility navigation (top right): Help, Get Help link, Days left in trial, Subscribe Now button (if trial) 

- • User profile menu with Settings, Logout options 

- • Notifications bell icon 

Primary Navigation Tabs: 

- • Horizontal tab bar below header 

- • Tabs: Home, Leads, Accounts, Contacts, Opportunities, Cases, Tasks, Calendar, Reports, Dashboards 

- • Active tab highlighted with underline or color 

- • Tab overflow handling for narrow screens (dropdown menu) 

Main Content Area: 

- • Full-width or with optional sidebar 

- • Page header with title and action buttons 

- • Content sections with clear visual separation 

### 6.2 Color Scheme 

Based on observed Salesforce Lightning interface: 

- • Primary: Dark blue (#032d60 or similar) for header and primary actions 

- • Secondary: Light blue (#0176d3 or similar) for links and accents 

- • Background: Light gray (#f3f3f3) for page background 

- • Cards/Panels: White (#ffffff) with subtle shadow 

- • Text: Dark gray (#181818) for body text 

- • Status colors: Green (success), Red (error), Yellow (warning), Blue (info) 

### 6.3 Typography 

- • Font family: Sans-serif system font stack (Salesforce Sans, Arial, Helvetica) 

- • Heading 1: 24px, bold 

- • Heading 2: 20px, semi-bold 

- • Heading 3: 16px, semi-bold 

- • Body text: 14px, regular 

- • Small text: 12px, regular (for metadata, timestamps) 

- • Line height: 1.5 for readability 

### 6.4 Form Design 

- • Two-column layout for forms on desktop (single column on mobile) 

- • Labels above input fields 

- • Required fields marked with red asterisk (*) 

- • Field-level help icons with tooltips 

- • Input field styling: Border on focus, validation states (error, success) 

- • Action buttons at bottom: Save (primary), Save & New (secondary), Cancel (tertiary) 

- • Form sections with collapsible/expandable panels 

### 6.5 Data Display 

List Views: 

- • Data table with alternating row colors for readability 

- • Column headers with sort indicators 

- • Row hover state for clarity 

- • Checkbox column for multi-select 

- • Row actions menu (Edit, Delete) on hover or click 

- • Pagination controls at bottom (Previous, Next, Page numbers) 

- • Record count display (e.g., Showing 1-25 of 150) 

Detail Views: 

- • Page header with record name and key information 

- • Information grouped into sections with headings 

- • Two-column field layout within sections 

- • Empty field handling (display dash or placeholder) 

- • Related lists below detail sections 

- • Tab interface if many related lists 

### 6.6 Icons and Visual Elements 

- • Consistent icon library (e.g., Material Icons, Font Awesome) 

- • Object icons for visual identification (Lead, Account, Contact, etc.) 

- • Action icons: Edit (pencil), Delete (trash), Add (plus), Search (magnifying glass) 

- • Loading spinners for async operations 

- • Empty state illustrations when no data 

### 6.7 Modal Dialogs 

- • Centered on screen with backdrop overlay 

- • Close button (X) in top right 

- • Modal header with title 

- • Modal body with content 

- • Modal footer with action buttons (right-aligned) 

- • Keyboard support: Esc to close, Tab navigation 

- • Focus trap within modal when open 

## 7. DATA REQUIREMENTS 

### 7.1 Data Model Details 

Lead Object Fields: 

- • Lead ID (auto-generated, primary key) 

- • Salutation (dropdown) 

- • First Name (text, 40 chars) 

- • Last Name (text, 80 chars, required) 

- • Company (text, 255 chars, required) 

- • Title (text, 128 chars) 

- • Email (email, 80 chars) 

- • Phone (text, 40 chars) 

- • Mobile (text, 40 chars) 

- • Lead Status (picklist, required) 

- • Lead Source (picklist) 

- • Rating (picklist) 

- • Industry (picklist) 

- • Annual Revenue (currency) 

- • Number of Employees (number) 

- • Website (URL, 255 chars) 

- • Address fields: Street, City, State, Postal Code, Country 

- • Description (long text) 

- • Converted (boolean) 

- • Converted Account ID (foreign key) 

- • Converted Contact ID (foreign key) 

- • Converted Opportunity ID (foreign key) 

- • Owner ID (foreign key to User) 

- • Created By, Created Date, Last Modified By, Last Modified Date (system fields) 

Account Object Fields: 

- • Account ID (auto-generated, primary key) 

- • Account Name (text, 255 chars, required) 

- • Parent Account ID (foreign key, self-referential) 

- • Phone (text, 40 chars) 

- • Fax (text, 40 chars) 

- • Website (URL, 255 chars) 

- • Type (picklist) 

- • Industry (picklist) 

- • Employees (number) 

- • Annual Revenue (currency) 

- • Billing Address fields 

- • Shipping Address fields 

- • Description (long text) 

- • Owner ID (foreign key) 

- • System fields 

Contact Object Fields: 

- • Contact ID (primary key) 

- • Account ID (foreign key, required) 

- • Salutation, First Name, Last Name 

- • Title, Department 

- • Phone, Mobile, Home Phone, Other Phone 

- • Email, Fax 

- • Reports To ID (foreign key to Contact) 

- • Assistant, Asst. Phone 

- • Mailing Address, Other Address 

- • Lead Source, Birthdate 

- • Description 

- • Owner ID, System fields 

Opportunity Object Fields: 

- • Opportunity ID (primary key) 

- • Opportunity Name (text, 120 chars, required) 

- • Account ID (foreign key, required) 

- • Amount (currency) 

- • Close Date (date, required) 

- • Stage (picklist, required) 

- • Probability (percentage) 

- • Type (picklist) 

- • Lead Source (picklist) 

- • Next Step (text, 255 chars) 

- • Description (long text) 

- • Is Closed (boolean, calculated from Stage) 

- • Is Won (boolean, calculated from Stage) 

- • Expected Revenue (calculated: Amount × Probability) 

- • Owner ID, System fields 

Case Object Fields: 

- • Case ID (primary key) 

- • Case Number (auto-generated, unique) 

- • Contact ID (foreign key, optional) 

- • Account ID (foreign key, optional) 

- • Subject (text, 255 chars) 

- • Description (long text) 

- • Status (picklist, required) 

- • Priority (picklist) 

- • Type (picklist) 

- • Case Origin (picklist, required) 

- • Case Reason (picklist) 

- • Internal Comments (long text) 

- • Web fields: Web Email, Web Name, Web Company, Web Phone 

- • Is Closed (boolean, calculated) 

- • Closed Date (datetime) 

- • Owner ID, System fields 

Task Object Fields: 

- • Task ID (primary key) 

- • Subject (text, 255 chars, required) 

- • Assigned To ID (foreign key to User) 

- • Related To Type (text) and Related To ID (polymorphic foreign key) 

- • Contact ID (foreign key, optional) 

- • Due Date (date) 

- • Status (picklist) 

- • Priority (picklist) 

- • Comments (long text) 

- • Reminder Set (boolean) 

- • Reminder Date/Time (datetime) 

- • System fields 

Event Object Fields: 

- • Event ID (primary key) 

- • Subject (text, 255 chars, required) 

- • Assigned To ID (foreign key to User) 

- • Related To Type and Related To ID (polymorphic) 

- • Contact ID (foreign key, optional) 

- • Start Date/Time (datetime, required) 

- • End Date/Time (datetime, required) 

- • All-Day Event (boolean) 

- • Location (text, 255 chars) 

- • Show Time As (picklist) 

- • Is Private (boolean) 

- • Description (long text) 

- • System fields 

### 7.2 Data Validation Rules 

- • Email fields must be valid email format 

- • URL fields must be valid URL format 

- • Phone fields can contain numbers, spaces, hyphens, parentheses, plus sign 

- • Currency and number fields must be numeric 

- • Date fields must be valid dates 

   - • Close Date on Opportunities cannot be in the past 

   - • Event End Date/Time must be after Start Date/Time 

   - • Opportunity Amount must be positive if entered 

   - • Probability must be between 0 and 100 

### 7.3 Data Integrity 

   - • Foreign key constraints enforced 

   - • Cascade delete for child records when parent deleted (configurable) 

   - • Unique constraints on Case Number and similar auto-generated fields 

   - • Database transactions for multi-step operations 

   - • Audit fields automatically updated (Created By/Date, Modified By/Date) 

## 8. INTEGRATION REQUIREMENTS 

### 8.1 Email Integration 

Outbound Email: 

- • Send emails from CRM interface 

- • Email templates for common scenarios 

- • Merge fields to personalize emails with record data 

- • Track email opens and clicks (optional) 

- • Email activity logged on related records 

System Notifications: 

- • Task assignment notifications 

- • Task due date reminders 

- • Record ownership change notifications 

- • Report subscription delivery 

- • Password reset emails 

### 8.2 File Storage 

- • Upload attachments to records (max 25MB per file) 

- • Supported file types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, images (JPG, PNG, GIF), text files 

- • Virus scanning for uploaded files 

- • File preview for common formats 

- • Download attachments 

- • Delete attachments (with permission check) 

### 8.3 API Capabilities 

RESTful API should support: 

- • CRUD operations for all major objects 

- • Query/search endpoints with filtering and sorting 

- • Bulk operations for data import/export 

- • Metadata endpoints to retrieve object schemas 

- • Authentication via API keys or OAuth 2.0 

- • Rate limiting to prevent abuse 

- • API documentation (Swagger/OpenAPI format) 

### 8.4 Data Import/Export 

Import: 

- • CSV file import for Leads, Accounts, Contacts, Opportunities 

- • Field mapping interface 

- • Data validation during import 

- • Error report for failed records 

- • Support for update existing records or insert new 

Export: 

- • Export list views to CSV or Excel 

- • Export reports with all rows 

- • Scheduled data exports (daily, weekly) 

### 8.5 Future Integration Considerations 

While not required in initial version, architecture should support future integration with: 

- • Email platforms (Gmail, Outlook) 

- • Calendar services (Google Calendar, Outlook Calendar) 

- • Marketing automation platforms 

- • Accounting systems 

- • Customer support/ticketing systems 

- • Document management systems 

## 9. APPENDICES 

### 9.1 Glossary 

See Section 1.3 for definitions and acronyms used throughout this document. 

### 9.2 Feature Priority Classification 

Must Have (P0) - Core Features: 

- • User authentication and authorization 

- • Lead, Account, Contact, Opportunity, Case management (basic CRUD) 

- • List views with sort and filter 

- • Detail views with related lists 

- • Global search 

- • Basic reporting 

Should Have (P1) - Important Features: 

- • Task and Event management 

- • Calendar views 

- • Dashboard creation 

- • Lead conversion workflow 

- • Email notifications 

- • Report builder 

- • Data import/export 

Could Have (P2) - Nice to Have Features: 

- • Report subscriptions 

- • Dashboard auto-refresh 

- • Account hierarchy visualization 

- • Advanced search 

- • File attachments with preview 

- • Multi-factor authentication 

Won't Have (P3) - Future Enhancements: 

- • Mobile native apps 

- • Workflow automation builder 

- • AI-powered insights and recommendations 

- • Custom object creation (admin) 

- • Third-party integrations marketplace 

### 9.3 Testing Requirements 

Unit Testing: 

- • Minimum 80% code coverage 

- • Test all business logic and data validation 

- • Mock external dependencies 

Integration Testing: 

- • Test API endpoints 

- • Test database operations 

- • Test email sending 

End-to-End Testing: 

- • Test critical user journeys (lead conversion, opportunity closure, case resolution) 

- • Cross-browser testing (Chrome, Firefox, Safari, Edge) 

- • Mobile responsive testing 

Performance Testing: 

- • Load testing with 100+ concurrent users 

- • Stress testing to identify breaking points 

- • Database query performance testing 

Security Testing: 

- • Penetration testing 

- • Vulnerability scanning 

- • Authentication and authorization testing 

### 9.4 Deployment Requirements 

- • Containerized deployment (Docker) 

- • Environment configurations: Development, Staging, Production 

- • CI/CD pipeline for automated testing and deployment 

- • Database migration scripts 

- • Rollback procedures 

- • Health check endpoints 

- • Logging and monitoring setup 

### 9.5 Documentation Deliverables 

Technical Documentation: 

- • System architecture diagrams 

- • Database schema documentation 

- • API documentation 

- • Deployment guide 

- • Development setup guide 

User Documentation: 

- • User guide/manual 

- • Quick start guide 

- • Administrator guide 

- • FAQs 

- • Video tutorials (optional) 

### 9.6 Assumptions and Dependencies 

Assumptions: 

- • Users have modern web browsers with JavaScript enabled 

- • Stable internet connection for all users 

- • Initial user base of 50-100 users 

- • English as primary language (other languages in future) 

Dependencies: 

- • SMTP server for email notifications 

- • SSL certificate for HTTPS 

- • Cloud or on-premise infrastructure for hosting 

- • Database server (PostgreSQL, MySQL, or similar) 

--- END OF DOCUMENT --- 

