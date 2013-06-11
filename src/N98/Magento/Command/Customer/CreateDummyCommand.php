<?php

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDummyCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $help = <<<HELP
Supported Locales:

- cs_CZ
- ru_RU
- bg_BG
- en_US
- it_IT
- sr_RS
- sr_Cyrl_RS
- sr_Latn_RS
- pl_PL
- en_GB
- de_DE
- sk_SK
- fr_FR
- es_AR
- de_AT
HELP;

        $this
            ->setName('customer:create:dummy')
            ->addArgument('count', InputArgument::REQUIRED, 'Count')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website')
            ->setDescription('Creates a dummy customers.')
            ->setHelp($help)
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $website = $this->getHelperSet()->get('parameter')->askWebsite($input, $output);

            for ($i = 0; $i < $input->getArgument('count'); $i++) {
                $customer = $this->getCustomerModel();

                $faker = \Faker\Factory::create($input->getArgument('locale'));

                $email = $faker->safeEmail;

                $customer->setWebsiteId($website->getId());
                $customer->loadByEmail($email);
                $password = $customer->generatePassword();

                if (!$customer->getId()) {
                    $customer->setWebsiteId($website->getId());
                    $customer->setEmail($email);
                    $customer->setPrefix($faker->prefix);
                    $customer->setFirstname($faker->firstName);
                    $customer->setLastname($faker->lastName);
                    $customer->setPassword($password);

                    $billingAddress = $this->getCustomerAddressModel();
                    $billingAddress->setStreet($faker->streetAddress);
                    $billingAddress->setCity($faker->city);
                    $billingAddress->setCountryId('US');
                    $billingAddress->setRegionId($faker->randomNumber(0,65));
                    $billingAddress->setPostcode($faker->postcode);
                    $billingAddress->setTelephone($faker->phoneNumber);
                    $billingAddress->setIsDefaultBilling(true);
                    $customer->addAddress($billingAddress);

                    $shippingAddress = $this->getCustomerAddressModel();
                    $shippingAddress->setStreet($faker->streetAddress);
                    $shippingAddress->setCity($faker->city);
                    $shippingAddress->setCountryId('US');
                    $shippingAddress->setRegionId($faker->randomNumber(0,65));
                    $shippingAddress->setPostcode($faker->postcode);
                    $shippingAddress->setTelephone($faker->phoneNumber);
                    $shippingAddress->setIsDefaultShipping(true);
                    $customer->addAddress($shippingAddress);

                    $customer->save();
                    $customer->setConfirmation(null);
                    $customer->save();

                    $output->writeln('<info>Customereee <comment>' . $email . '</comment> with password <comment>' . $password .  '</comment> successfully created</info>');
                } else {
                    $output->writeln('<error>Customer ' . $email . ' already exists</error>');
                }
            }

        }
    }
}