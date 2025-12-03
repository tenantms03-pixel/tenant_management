# Looking to send emails in production? Check out our Email API/SMTP product!
import smtplib

sender = "Private Person <from@example.com>"
receiver = "A Test User <to@example.com>"

message = f"""\
Subject: Hi Mailtrap
To: {receiver}
From: {sender}

This is a test e-mail message."""

with smtplib.SMTP("sandbox.smtp.mailtrap.io", 2525) as server:
    server.starttls()
    server.login("14a23c920ee3aa", "758362415a9f2c")
    server.sendmail(sender, receiver, message)