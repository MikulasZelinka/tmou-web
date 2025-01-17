<?php declare(strict_types=1);
namespace InstruktoriBrno\TMOU\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;

class EventFormFactory
{
    use SmartObject;

    /** @var FormFactory */
    private $factory;

    public function __construct(FormFactory $factory)
    {
        $this->factory = $factory;
    }
    public function create(callable $onSuccess): Form
    {
        $form = $this->factory->create();

        $form->addGroup('Obecné');
        $form->addText('name', 'Název')
            ->setRequired('Vyplňte, prosím, název ročníku.')
            ->addRule(Form::MAX_LENGTH, 'Název ročníku může být maximálně 255 znaků dlouhý.', 255);
        $form->addText('motto', 'Motto')
            ->setRequired(false)
            ->addRule(Form::MAX_LENGTH, 'Motto ročníku může být maximálně 255 znaků dlouhý.', 255);
        $form->addText('number', 'Číslo (ročník)')
            ->setType('number')
            ->setHtmlAttribute('step', 1)
            ->setHtmlAttribute('min', 1)
            ->setRequired('Vyplňte, prosím, číslo ročníku.')
            ->addRule(Form::MIN, 'Číslo ročníku musí být kladné.', 1);

        $form->addGroup('Kvalifikace');
        $form->addCheckbox('hasQualification', 'Má kvalifikaci');
        $form->addDateTimePicker('qualificationStart', 'Začátek')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addDateTimePicker('qualificationEnd', 'Konec')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addText('qualifiedTeamCount', 'Kvalifikujících se týmů')
            ->setType('number')
            ->setHtmlAttribute('step', 1)
            ->setHtmlAttribute('min', 0);

        $form->addGroup('Hra');
        $form->addDateTimePicker('registrationDeadline', 'Deadline registrace')
            ->setOption('description', 'Lze ponechat prázdné, v takovém případě nebude registrace otevřena.')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addDateTimePicker('changeDeadline', 'Deadline změn týmů')
            ->setOption('description', 'Lze ponechat prázdné, v takovém případě budou změny týmů povoleny až do začátku hry.')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addDateTimePicker('eventStart', 'Začátek')
            ->setRequired('Vyplňte, prosím, začátek hry')
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addDateTimePicker('eventEnd', 'Konec')
            ->setRequired('Vyplňte, prosím, konec hry.');
        $form->addText('totalTeamCount', 'Celkový počet týmů')
            ->setType('number')
            ->setHtmlAttribute('step', 1)
            ->setHtmlAttribute('min', 1)
            ->setHtmlAttribute('autocomplete', 'off');

        $form->addGroup('Placení');
        $form->addText('paymentPairingCodePrefix', 'Prefix VS');
        $form->addText('paymentPairingCodeSuffixLength', 'Délka sufixu VS')
            ->setOption('description', 'Na kolik míst bude formátováno číslo týmu.');

        $form->addPrimarySubmit('send', 'Uložit');
        $form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
            $onSuccess($form, $values);
        };
        return $form;
    }
}
