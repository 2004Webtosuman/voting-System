# Smart Parking Slot Booking System

A comprehensive parking management system with AI-powered predictions, real-time slot booking, and QR-based entry/exit system.

## 🚀 Features

### Core Features
- **Interactive Parking Map**: Real-time visualization of parking availability
- **Smart Slot Booking**: Reserve parking slots in advance
- **QR Code Entry/Exit**: Contactless parking experience
- **AI Predictions**: Machine learning-powered peak time predictions
- **Admin Dashboard**: Comprehensive management interface
- **User Management**: Registration, authentication, and profile management

### Technical Features
- **Dijkstra's Algorithm**: Find nearest available parking slot
- **Regression Analysis**: Predict parking occupancy rates
- **Real-time Updates**: Live parking availability status
- **Responsive Design**: Mobile-friendly interface
- **RESTful API**: Clean API architecture

## 🛠️ Tech Stack

### Frontend
- **HTML5/CSS3/JavaScript**: Modern web technologies
- **Leaflet.js**: Interactive maps
- **Chart.js**: Data visualization
- **QR Code.js**: QR code generation

### Backend
- **PHP**: Server-side logic
- **MySQL**: Database management
- **PDO**: Database abstraction layer

### AI/Algorithms
- **Linear Regression**: Peak time predictions
- **Dijkstra's Algorithm**: Optimal pathfinding
- **Statistical Analysis**: Usage pattern recognition

## 📁 Project Structure

```
smart-parking/
├── api/                    # Backend API
│   ├── auth/              # Authentication endpoints
│   ├── models/            # Data models
│   ├── algorithms/        # AI and pathfinding algorithms
│   ├── ai/               # AI prediction system
│   ├── bookings/         # Booking management
│   ├── slots/            # Parking slot operations
│   ├── qr/               # QR code verification
│   └── admin/            # Admin-only endpoints
├── frontend/              # User interface
│   ├── css/              # Stylesheets
│   ├── js/               # Client-side JavaScript
│   └── index.html        # Main application
├── admin/                 # Admin panel
│   ├── css/              # Admin-specific styles
│   ├── js/               # Admin functionality
│   └── index.html        # Admin dashboard
├── config/                # Configuration files
├── database/              # Database schema and setup
└── README.md
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Database Setup

1. Create MySQL database:
```sql
CREATE DATABASE smart_parking;
```

2. Import the schema:
```bash
mysql -u your_username -p smart_parking < database/schema.sql
```

3. Configure database connection in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'smart_parking';
private $username = 'your_username';
private $password = 'your_password';
```

### Web Server Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd smart-parking
```

2. Configure your web server to serve the project directory

3. Ensure PHP has the following extensions:
   - PDO
   - PDO_MySQL
   - JSON

### Access the Application

- **User Interface**: `http://your-domain/frontend/`
- **Admin Panel**: `http://your-domain/admin/`

## 👤 Default Accounts

### Admin Account
- **Email**: admin@smartparking.com
- **Password**: password
- **Role**: Administrator

### Test User Accounts
- **Email**: john@example.com / Password: password
- **Email**: jane@example.com / Password: password

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/login.php` - User login
- `POST /api/auth/register.php` - User registration

### Parking Slots
- `POST /api/slots/find_nearest.php` - Find nearest available slot
- `GET /api/slots/available.php` - Get all available slots

### Bookings
- `POST /api/bookings/create.php` - Create new booking
- `GET /api/bookings/user_bookings.php` - Get user bookings
- `PUT /api/bookings/cancel.php` - Cancel booking

### QR System
- `POST /api/qr/verify.php` - Verify QR code for entry/exit

### AI Predictions
- `GET /api/ai/predictions.php` - Get parking predictions
- `GET /api/ai/predictions.php?type=peak_hours` - Get peak hours analysis

### Admin Endpoints
- `GET /api/admin/zones.php` - Manage parking zones
- `GET /api/admin/bookings.php` - View all bookings
- `GET /api/admin/users.php` - Manage users

## 🤖 AI Prediction System

The system uses linear regression to predict parking occupancy based on:

- **Time factors**: Hour of day, day of week, season
- **Historical data**: Past occupancy patterns
- **External factors**: Weather conditions, holidays
- **Zone characteristics**: Location, capacity, pricing

### Prediction Features
- Hourly occupancy forecasts
- Peak time identification
- Confidence intervals
- Real-time model updates

## 🧮 Dijkstra's Algorithm Implementation

The pathfinding system considers:

- **Distance**: Physical distance to parking zones
- **Traffic conditions**: Real-time traffic simulation
- **Zone capacity**: Available slots in each zone
- **User preferences**: Slot type, price sensitivity

## 📱 QR Code System

### Entry Process
1. User receives QR code upon booking confirmation
2. QR code contains encrypted booking information
3. Scanner verifies code and updates booking status
4. Parking slot is marked as occupied

### Exit Process
1. User scans QR code at exit
2. System calculates actual parking duration
3. Processes any additional charges
4. Releases parking slot

## 🎨 User Interface Features

### Interactive Map
- Real-time slot availability
- Color-coded status indicators
- Click-to-book functionality
- Route guidance to selected slot

### Responsive Design
- Mobile-optimized interface
- Touch-friendly controls
- Adaptive layouts
- Cross-browser compatibility

### Dashboard Features
- Booking history
- Payment tracking
- QR code management
- Preference settings

## 👨‍💼 Admin Panel Features

### Dashboard
- Real-time statistics
- Revenue tracking
- Occupancy analytics
- Recent activity feed

### Zone Management
- Add/edit parking zones
- Configure slot layouts
- Set pricing tiers
- Monitor zone performance

### User Management
- View user accounts
- Manage permissions
- Track user activity
- Handle support requests

### Analytics
- Usage patterns
- Revenue reports
- Predictive analytics
- Performance metrics

## 🔒 Security Features

- **Authentication**: JWT-based token system
- **Authorization**: Role-based access control
- **Data Validation**: Input sanitization and validation
- **SQL Injection Prevention**: Parameterized queries
- **XSS Protection**: Output encoding
- **CSRF Protection**: Token-based request validation

## 📊 Database Schema

### Core Tables
- `users`: User account information
- `parking_zones`: Parking area definitions
- `parking_slots`: Individual parking spaces
- `bookings`: Reservation records
- `qr_codes`: QR code tokens and validation

### Analytics Tables
- `parking_history`: Historical occupancy data
- `user_activity`: User interaction logs
- `revenue_tracking`: Financial records

## 🚀 Performance Optimizations

- **Database Indexing**: Optimized query performance
- **Caching**: Redis/Memcached integration ready
- **CDN Ready**: Static asset optimization
- **Lazy Loading**: Efficient resource loading
- **API Rate Limiting**: Prevents abuse

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Parking slot search and booking
- [ ] QR code generation and verification
- [ ] Admin panel functionality
- [ ] Mobile responsiveness
- [ ] AI prediction accuracy

### API Testing
Use tools like Postman or curl to test API endpoints:

```bash
# Test login
curl -X POST http://your-domain/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@smartparking.com","password":"password"}'

# Test slot search
curl -X POST http://your-domain/api/slots/find_nearest.php \
  -H "Content-Type: application/json" \
  -d '{"latitude":40.7128,"longitude":-74.0060,"slot_type":"regular"}'
```

## 🔧 Configuration

### Environment Variables
Create a `.env` file for environment-specific settings:

```env
DB_HOST=localhost
DB_NAME=smart_parking
DB_USER=your_username
DB_PASS=your_password
JWT_SECRET=your_jwt_secret
API_BASE_URL=http://your-domain/api
```

### Customization Options
- Modify `config/database.php` for database settings
- Update styling in CSS files
- Configure map settings in JavaScript files
- Adjust AI model parameters in prediction classes

## 📈 Future Enhancements

### Planned Features
- **Mobile App**: Native iOS/Android applications
- **Payment Integration**: Stripe/PayPal integration
- **IoT Sensors**: Real-time occupancy detection
- **Machine Learning**: Advanced prediction models
- **Multi-language Support**: Internationalization
- **Push Notifications**: Real-time alerts

### Scalability Improvements
- **Microservices Architecture**: Service decomposition
- **Load Balancing**: High availability setup
- **Database Sharding**: Horizontal scaling
- **Cloud Integration**: AWS/Azure deployment

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Email: support@smartparking.com
- Documentation: [Project Wiki]

## 🙏 Acknowledgments

- OpenStreetMap for mapping data
- Chart.js for visualization components
- Leaflet.js for interactive maps
- QR Code.js for QR code generation

---

**Smart Parking System** - Making parking smarter, one slot at a time! 🚗✨