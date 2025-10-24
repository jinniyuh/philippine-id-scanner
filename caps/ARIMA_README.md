# ARIMA Forecasting Implementation for Veterinary Management System

## Overview

This implementation adds **ARIMA (AutoRegressive Integrated Moving Average)** forecasting capabilities to the Bago City Veterinary Office Inventory Management System. ARIMA is a powerful time-series forecasting model that predicts future values based on historical trends.

## What is ARIMA?

ARIMA stands for **AutoRegressive Integrated Moving Average** and consists of three components:

1. **AR (AutoRegressive)**: Uses past values to predict future values
2. **I (Integrated)**: Applies differencing to make the time series stationary
3. **MA (Moving Average)**: Uses past forecast errors to predict future values

### Model Configuration: ARIMA(1,1,1)
- **AR(1)**: Uses 1 lag of past values
- **I(1)**: Applies first-order differencing
- **MA(1)**: Uses 1 lag of past forecast errors

## Features Implemented

### 1. Pharmaceutical Demand Forecasting
- Predicts future pharmaceutical usage based on historical transaction data
- Helps with inventory planning and stock management
- Identifies trends in medicine consumption

### 2. Livestock Population Forecasting
- Forecasts livestock population growth/decline
- Helps plan veterinary services and resource allocation
- Tracks population trends by animal type

### 3. Poultry Population Forecasting
- Similar to livestock but specifically for poultry
- Monitors poultry farming trends
- Assists in vaccination and health program planning

### 4. Transaction Volume Forecasting
- Predicts future transaction volumes
- Helps with staffing and resource planning
- Identifies busy periods and seasonal patterns

### 5. Low Stock Predictions
- Uses ARIMA to predict when pharmaceuticals will run out
- Provides early warning for restocking
- Calculates days until stockout

### 6. Seasonal Trend Analysis
- Analyzes monthly transaction patterns
- Identifies peak and low seasons
- Helps with seasonal planning

## Files Created/Modified

### New Files:
1. **`includes/arima_forecaster.php`** - Core ARIMA implementation
2. **`get_ml_insights.php`** - API endpoint for ML insights
3. **`admin_ml_insights.php`** - Comprehensive ML insights dashboard
4. **`generate_sample_data.php`** - Sample data generator for testing
5. **`ARIMA_README.md`** - This documentation file

### Modified Files:
1. **`admin_dashboard.php`** - Added ML insights widget
2. **`includes/admin_sidebar.php`** - Added ML Insights navigation link

## How to Use

### 1. Generate Sample Data (First Time Setup)
```bash
# Navigate to the application
http://localhost/capstone/generate_sample_data.php
```
This will populate the database with sample data for testing the ARIMA forecasting.

### 2. Access ML Insights
- **Dashboard Widget**: View quick insights on the admin dashboard
- **Detailed View**: Click "View Details" or navigate to "ML Insights" in the sidebar
- **API Access**: Use `get_ml_insights.php` for programmatic access

### 3. Understanding the Forecasts

#### Pharmaceutical Demand Forecast
- **Blue Line**: Historical usage data
- **Red Dashed Line**: Predicted future demand
- **Trend Indicator**: Shows if demand is increasing/decreasing

#### Population Forecasts
- **Livestock**: Cattle, pigs, goats, sheep, carabao
- **Poultry**: Chickens, ducks, turkeys, quail, pigeons
- **Trend Analysis**: Population growth or decline patterns

#### Low Stock Predictions
- **Critical**: Items that may run out within 7 days
- **Warning**: Items that may run out within 14 days
- **Monitor**: Items that need attention soon

## Technical Implementation

### ARIMA Algorithm Details

```php
class ARIMAForecaster {
    // Core ARIMA implementation
    public function forecast($steps = 1) {
        // 1. Apply differencing (I component)
        $diffed_data = $this->difference($this->data, $this->d);
        
        // 2. Estimate AR parameters
        $ar_params = $this->estimateARParameters($diffed_data, $this->p);
        
        // 3. Generate forecasts
        $forecast_diffed = $this->generateForecast($diffed_data, $ar_params, $steps);
        
        // 4. Inverse differencing to get final forecast
        $forecast = $this->inverseDifference($forecast_diffed, $this->data, $this->d);
        
        return $forecast;
    }
}
```

### Key Mathematical Components

1. **Differencing**: `diff(t) = y(t) - y(t-1)`
2. **Autocorrelation**: Measures correlation between observations at different time lags
3. **AR Parameter Estimation**: Uses Yule-Walker equations
4. **Forecast Generation**: Combines AR and trend components

### Data Requirements

- **Minimum Data Points**: 3+ observations for meaningful forecasts
- **Time Period**: Monthly data for the past 12 months
- **Data Quality**: Consistent time intervals and complete records

## API Endpoints

### GET `/get_ml_insights.php`
Returns comprehensive ML insights in JSON format:

```json
{
  "success": true,
  "insights": {
    "pharmaceutical_demand": {
      "title": "Pharmaceutical Demand Forecast",
      "forecast": [45, 52, 48],
      "historical": [30, 35, 40, 38, 42, 45],
      "trend": "increasing"
    },
    "low_stock_alerts": {
      "predictions": [
        {
          "name": "Dewormer",
          "current_stock": 5,
          "predicted_demand": 8,
          "days_until_stockout": 3
        }
      ]
    },
    "recommendations": [
      {
        "type": "urgent",
        "message": "Critical: 1 pharmaceutical(s) may run out within a week",
        "action": "Immediate restocking required"
      }
    ]
  },
  "generated_at": "2025-01-02 10:30:00",
  "model": "ARIMA(1,1,1)"
}
```

## Benefits for Veterinary Management

### 1. Proactive Inventory Management
- Predict stockouts before they happen
- Optimize reorder timing and quantities
- Reduce emergency purchases

### 2. Resource Planning
- Plan veterinary services based on population trends
- Allocate staff and resources efficiently
- Prepare for seasonal variations

### 3. Cost Optimization
- Reduce inventory holding costs
- Minimize stockout costs
- Improve cash flow management

### 4. Data-Driven Decisions
- Make informed decisions based on trends
- Identify patterns in animal health needs
- Plan vaccination and health programs

## Limitations and Considerations

### 1. Data Quality
- Requires consistent, clean historical data
- Missing data can affect forecast accuracy
- Seasonal patterns need sufficient historical data

### 2. Model Assumptions
- Assumes linear trends in the data
- May not capture sudden changes or external factors
- Requires stationary time series after differencing

### 3. Forecast Horizon
- Short to medium-term forecasts are most accurate
- Long-term forecasts have higher uncertainty
- Regular model updates recommended

## Future Enhancements

### 1. Advanced Models
- **SARIMA**: Seasonal ARIMA for seasonal patterns
- **ARIMAX**: ARIMA with external variables
- **Prophet**: Facebook's forecasting tool

### 2. Additional Features
- **Confidence Intervals**: Show forecast uncertainty
- **Model Comparison**: Compare different forecasting methods
- **Automated Alerts**: Email/SMS notifications for critical predictions

### 3. Integration
- **External Data**: Weather, economic indicators
- **Real-time Updates**: Live data feeds
- **Mobile App**: Push notifications for insights

## Troubleshooting

### Common Issues

1. **"Insufficient data for forecasting"**
   - Solution: Generate more sample data or wait for more historical data

2. **"Error loading insights"**
   - Check database connection
   - Verify table structure
   - Check PHP error logs

3. **Forecasts seem inaccurate**
   - Review data quality
   - Check for outliers
   - Consider adjusting ARIMA parameters

### Performance Optimization

1. **Caching**: Cache forecasts for 5-10 minutes
2. **Database Indexing**: Index date columns for faster queries
3. **Batch Processing**: Process forecasts during off-peak hours

## Support and Maintenance

### Regular Tasks
- Monitor forecast accuracy
- Update model parameters if needed
- Clean and validate data regularly
- Backup forecast results

### Monitoring
- Check forecast vs actual values
- Monitor system performance
- Review user feedback
- Track business impact

---

**Note**: This ARIMA implementation is designed for educational and demonstration purposes. For production use in critical veterinary operations, consider consulting with data science professionals and implementing additional validation and testing procedures.
