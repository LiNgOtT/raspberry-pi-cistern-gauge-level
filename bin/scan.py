#!/usr/bin/python
# coding=utf8
# ###############################################
#
# You need to set up the following variables to
# get the scripte working well
#
# - cistern_radius
# - distance_sensor_to_ground
# - csv_file_path
# - ftp_host
# - ftp_user
# - ftp_password
#
# ###############################################

# set the distance of the sensor to your cistern gauge in cm
distance_sensor_to_ground=230

# set radius of your cistern gauge
cistern_radius=112.5

# path to the csv file to story data to
csv_file_path='/var/www/raspberry_gauge/var/raspberry/cistern.csv'

# set ftp credentials for csv file upload
ftp_host=''
ftp_user=''
ftp_password=''

# ###############################################
# ############# BELOW NO EDIT NEEDED ############
# ###############################################

# import libs
import time
import datetime
import ftplib
import RPi.GPIO as GPIO

# set GPIO pin allocation
GPIO_pin_allocation_trigger=11
GPIO_pin_allocation_echo=13

# GPIO setup
GPIO.setwarnings(False)
GPIO.setmode(GPIO.BOARD)
GPIO.setup(GPIO_pin_allocation_echo,GPIO.IN)
GPIO.setup(GPIO_pin_allocation_trigger,GPIO.OUT)

# initiate some vars for calculation
#
# scan_distance: distance for each scan loop
# scan_distance_total: sum of all distance scans
#
scan_distance=0
scan_distance_total=0

# mathematical formula for liter calculation per cm
#
# pi * radius * radius * 1cm / 1000
#
math_formula_liter_per_cm=3.1415*cistern_radius*cistern_radius*1/1000

# debug
print "Liter per cm: ",math_formula_liter_per_cm

# amount of scans we do to get a good average
scan_runs=50

# do the scans
for i in range(0,scan_runs):

        GPIO.output(GPIO_pin_allocation_trigger,True)
        time.sleep(0.00001)
        GPIO.output(GPIO_pin_allocation_trigger,False)

        while GPIO.input(GPIO_pin_allocation_echo) == 0:
                pass
        start=time.time();

        while GPIO.input(GPIO_pin_allocation_echo) == 1:
                pass
        ende = time.time();

        scan_distance=((ende - start) * 27600) / 2
        print scan_distance
        scan_distance_total=scan_distance_total+scan_distance
        time.sleep(0.75)

# calculate average distance
scan_distance_average=scan_distance_total/scan_runs

# calculate volume
volume=(distance_sensor_to_ground-scan_distance_average)*math_formula_liter_per_cm

# debug
print "Distance: ",  scan_distance_average, " cm"
print "Volume: ", volume
print "- - - - - - - - - - - - - - - - - - - - - "

# write current date and water gauge to file
if csv_file_path:
    i = datetime.datetime.now()
    file_cistern=open(csv_file_path,'a')
    file_cistern.write("%s,%s\n" % ((i), (volume)));
    file_cistern.close()
else:
    print "No CSV file path set"

# upload csv file to ftp
if ftp_host:
    file_cistern=open(csv_file_path,'a')
    serverftp = ftplib.FTP(ftp_host, ftp_user, ftp_password)
    serverftp.storbinary('Stor cistern.csv', file_cistern)
    serverftp.quit()
    file_cistern.close()
else:
    print "No ftp credentials set - NO upload"
