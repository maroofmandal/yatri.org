---
description: SSH access to yatri.org production server
---

## Connect to yatri.org Production Server

1. SSH into the server as ubuntu:
```
ssh -i ~/.ssh/sattaz-key ubuntu@155.248.246.43
```

2. Switch to the yatri application user:
```
sudo su - yatri
```

3. Navigate to the project root:
```
cd ~/htdocs/yatri.org
```
