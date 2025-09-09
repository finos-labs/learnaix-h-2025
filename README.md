# NatWest Hack4aCause Hackathon

![badge-labs](https://user-images.githubusercontent.com/327285/230928932-7c75f8ed-e57b-41db-9fb7-a292a13a1e58.svg)

This repository was forked from [finos-labs/learnaix-h-2025](https://github.com/finos-labs/learnaix-h-2025).

Please refer to the [HACK4ACAUSE-TEMPLATE_README.md](./HACK4ACAUSE-TEMPLATE_README.md) for complete submission instructions and project documentation requirements.

---

&nbsp;

Join us in a transformative AI hackathon focused on driving social impact in education and
employability. Let’s work together to add the next game changer feature on to Snowflake
LearnAIx platform, the AI-assisted, open-source learning system that you all will be helping
build via this hackathon.

## Purpose:

With LearnAIx, our goal is to develop a scalable and accessible platform that can be
leveraged by non-profits and academies alike to enhance learning experiences and open
doors of opportunities to all learners.

Through your involvement in this hackathon, you will get the opportunity to socialise and
network with like-minded enthusiasts who believe in open-source enablers for community
benefit. You will use AI to improve learning solutions in the education sector.

## Objective:

To create AI enabled Moodle plugins on existing LearnAIx alpha solution on Snowflake public
platform. Shortlisted plugins will feature in the LearnAIx beta version that will be available to consume for free.

### Integrations & Tech stack

Plugins: Custom Moodle plugins using PHP, JavaScript, and REST APIs
AI Integration: OpenAI APIs, LangChain, or Python-based microservices
Hosting: Snowflake

## How to get started with Plugin Development
- Clone the repo
- Navigate to ```assets``` folder
- ### Step 1: Choose your runtime
#### You can choose to run it in one of two ways:  
##### Option 1: Run Plugin on Snowflake
- No need to install Moddodle
- you can run the plugin directly inside your Snowflake environment.  
- *Note:* If you only want to use Snowflake, you can *skip the local setup steps* below.

##### Option 2: Run Plugin Locally with Moodle
- Install Moodle on your local machine.  
- Follow the setup guide for your OS:  
  - [Windows Guide](./assets/moodle-local-setup/moodle-windows-setup.md)  
  - [macOS/iOS Guide](./assets/moodle-local-setup/moodle-macos-setup.md)   
- Complete the setup steps as per the guide.

- ### Step 2: Create Your Plugin
Once you’ve decided where to run the plugin (Snowflake or Moodle), the next step is to *create your plugin*.  

We have provided *different plugin templates* inside the repository:  
- *With PHP support* → See the [with php](./assets/plugin-develpment-templates/with-php/)  
- *Without PHP support* → See the [without php](./assets/plugin-develpment-templates/without-php/)  

Explore these folders and pick the template that best fits your needs.  

## License

&copy; Copyright 2025 FINOS

Distributed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0).

SPDX-License-Identifier: [Apache-2.0](https://spdx.org/licenses/Apache-2.0)
