import BeautifulSoup
import re
import urllib2
from datetime import datetime, timedelta
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import uuid

# http://simp.ly/publish/sjNH59 MAIN
# http://simp.ly/publish/KSsymw ARCHIVE


# Get the contents of a simplenote
def getSimpleNoteContents(url):

    response = urllib2.urlopen(url)

    data = response.read()

    dom = BeautifulSoup.BeautifulSoup(data)

    elm1 = dom.find(None, {"id": "content"})

    contents = elm1.renderContents()
    lines = contents.split('<br />')
    return lines

archiveLines = getSimpleNoteContents('http://simp.ly/publish/KSsymw')

newLines = getSimpleNoteContents('http://simp.ly/publish/sjNH59')

lines = []
lines.extend(archiveLines)
lines.extend(newLines)

days = {}
date = False
day = False
feeding = {}
key = False
lastFeed = False
foundPM = False
thisFeedCount = 0
lastEventTime = False
foundPM = False

now = datetime.now()
backupFile = now.strftime("BabyLogLoad_%Y%m%d%H%M.sql")

f = open(backupFile, 'w')


# Build functions
def nextDate(current):
    global foundPM
    nextDate = current + timedelta(days=1)
    key = datetime.strftime(nextDate, "%m/%d/%Y")
    foundPM = False
    return key


def processSleep(sleepStart, sleepEnd):
    sleepPeriod = 0
    print "Sleep Detected: ", sleepStart, sleepEnd
    sleepPeriod = sleepEnd - sleepStart
    print "Sleep Period: ", sleepPeriod
    return sleepPeriod


def setupDate(nextKey):
    global day, key, days, date, lastFeed, daySleep, f, foundPM, lastEventTime
    #print "found a match", match.group()
    if day and key:
        print "storing new key: ", day, key
        days[key] = day
    date = datetime.strptime(nextKey, "%m/%d/%Y")
    key = datetime.strftime(date, "%m/%d/%Y")
    print "Generating a new date: ", key
    day = {'date': key, 'feedings': 0, 'wet diapers': 0, \
    'right boob': 0, 'left boob': 0, 'top off': 0, 'sole bottle': 0, 'sleep total': 0, \
    'sleep periods': '', 'sleep methods': '', 'time on boob': 0, 'bottle size': 0}
    day['deltas'] = []
    # lastFeed = False
    lastEventTime = False
    foundPM = False
    daySleep = False
    return key


def getDateTime(current):
    global lastEventTime, key, date, foundPM, f
    checkTime = datetime.strptime(current, '%m/%d/%Y %I:%M')
    if lastEventTime:
        if foundPM and checkTime.hour < lastEventTime.hour:
            nextKey = nextDate(date)
            key = setupDate(nextKey)
            foundPM = False
        elif checkTime.hour < lastEventTime.hour:
            foundPM = True

    lastEventTime = checkTime

    if foundPM:
        current += 'PM'
        print "Found PM"
    else:
        current += 'AM'

    dateTime = datetime.strptime(current, '%m/%d/%Y %I:%M%p')
    return dateTime


# Last occurence
lastVitaminD = False
lastBath = False
feedTimeOnBoob = 0
feedBottleOunces = 0
lastWeight = False
lastWeightDate = False
weights = []
loadQuery = ""


def writeEvent(eventDate, eventId, eventType, content):
    global loadQuery
    if len(loadQuery) > 0:
        loadQuery += ","
    loadQuery += "('" + str(eventId) + "', " + str(eventType) + ", 1, '" + str(eventDate) + "', '" + content + "', now())\n"

for line in lines:
    print line, "\n"
    # Check for a date
    dateMatch = re.search(r'[0-9]+\/[0-9]+\/[0-9]+', line)
    if dateMatch:
        nextKey = dateMatch.group()
        key = setupDate(nextKey)
        print "Date Found: ", key
        continue

    # Check for an event
    timeMatch = re.search(r'[0-9]+:[0-9]+', line)
    if timeMatch:
        timeString = timeMatch.group()
        timeString = key + ' ' + timeString
        eventTime = getDateTime(timeString)
        print "Event Time: ", str(eventTime)

        eventUUID = uuid.uuid1()
        eventUUIDString = str(eventUUID).replace('-', '')

        # What type of event is it?
        content = ""
        eventType = 0

        # Feed Detection
        feedMatch = re.search(r'\-', line)
        if feedMatch:
            parts = line.split('-')
            content = parts[1].strip()

        # Check for wet diaper
        diaperMatch = re.search(r'\*', line)
        if diaperMatch:
            parts = line.split('*')
            eventType = 1
            content = parts[1].strip()

        # Check for general event
        eventMatch = re.search(r'\&gt\;', line)
        if eventMatch:
            print "general event"
            parts = line.split('&gt;')
            eventType = 2
            content = parts[1].strip()

        # Check for sleep tracking
        sleepMatch = re.search(r'\$', line)
        if sleepMatch:
            print "sleep start: ", eventTime
            parts = line.split('$')
            eventType = 3
            content = parts[1].strip()

        writeEvent(eventTime, eventUUIDString, eventType, content)

query = "truncate events; insert into events(event_id, type, user_id, event_date, content, created_at) values\n" + loadQuery + ";\n"

f.write(query)

f.close()

f = open('load.sql', 'w')
f.write(query)
f.close()




